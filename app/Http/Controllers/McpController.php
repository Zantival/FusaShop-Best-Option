<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Consumer\AddressController;
use App\Models\Order;
use App\Models\Product;
use App\Services\EnviaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Servidor MCP (Model Context Protocol) sobre HTTP, JSON-RPC 2.0.
 * Métodos: initialize, tools/list, tools/call. Solo lectura. Autenticado con Bearer MCP_TOKEN.
 */
class McpController extends Controller
{
    private function tools(): array
    {
        return [
            [
                'name' => 'get_order',
                'description' => 'Consulta el estado de un pedido por su referencia (FS-...).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => ['reference' => ['type' => 'string', 'pattern' => '^FS-[A-Z0-9]{10,40}$']],
                    'required' => ['reference'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'search_catalog',
                'description' => 'Busca productos activos del catálogo con stock disponible.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'maxLength' => 100],
                        'category' => ['type' => 'string', 'maxLength' => 60],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 25, 'default' => 10],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'validate_shipping',
                'description' => 'Valida una dirección de envío colombiana y opcionalmente cotiza el flete de un producto.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'postal_code' => ['type' => 'string', 'pattern' => '^[0-9]{6}$'],
                        'state' => ['type' => 'string', 'enum' => array_keys(AddressController::STATES)],
                        'city' => ['type' => 'string', 'maxLength' => 100],
                        'street' => ['type' => 'string', 'maxLength' => 160],
                        'product_id' => ['type' => 'integer', 'minimum' => 1],
                        'quantity' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 1],
                    ],
                    'required' => ['postal_code', 'state', 'city', 'street'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    public function __invoke(Request $request, EnviaService $envia): JsonResponse
    {
        $token = (string) config('services.fusashop.mcp_token');
        if ($token === '' || !hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32001, 'message' => 'Unauthorized']], 401);
        }

        $id = $request->input('id');
        $method = (string) $request->input('method');
        $params = (array) $request->input('params', []);

        $result = match ($method) {
            'initialize' => [
                'protocolVersion' => '2024-11-05',
                'serverInfo' => ['name' => 'fusashop-mcp', 'version' => '1.0.0'],
                'capabilities' => ['tools' => new \stdClass()],
            ],
            'tools/list' => ['tools' => $this->tools()],
            'tools/call' => $this->call((string) ($params['name'] ?? ''), (array) ($params['arguments'] ?? []), $envia),
            default => null,
        };

        if ($result === null) {
            return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => 'Method not found']]);
        }

        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
    }

    private function call(string $name, array $args, EnviaService $envia): array
    {
        try {
            $data = match ($name) {
                'get_order' => $this->getOrder($args),
                'search_catalog' => $this->searchCatalog($args),
                'validate_shipping' => $this->validateShipping($args, $envia),
                default => throw new \InvalidArgumentException("Unknown tool: {$name}"),
            };
            return ['content' => [['type' => 'text', 'text' => json_encode($data, JSON_UNESCAPED_UNICODE)]], 'isError' => false];
        } catch (Throwable $e) {
            return ['content' => [['type' => 'text', 'text' => $e->getMessage()]], 'isError' => true];
        }
    }

    private function getOrder(array $a): array
    {
        $v = Validator::make($a, ['reference' => ['required', 'regex:/^FS-[A-Z0-9]{10,40}$/']])->validate();
        $o = Order::with('items.product:id,name')->where('reference', $v['reference'])->first();
        if (!$o) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'reference' => $o->reference,
            'stage' => $o->stageLabel(),
            'payment_status' => $o->payment_status,
            'total' => (float) $o->total,
            'currency' => $o->currency,
            'carrier' => $o->shipping_carrier,
            'tracking_number' => $o->tracking_number,
            'invoice_number' => $o->invoice_number,
            'items' => $o->items->map(fn ($i) => ['name' => $i->product?->name, 'quantity' => $i->quantity, 'price' => (float) $i->price])->all(),
        ];
    }

    private function searchCatalog(array $a): array
    {
        $v = Validator::make($a, [
            'query' => 'nullable|string|max:100', 'category' => 'nullable|string|max:60', 'limit' => 'nullable|integer|min:1|max:25',
        ])->validate();

        return Product::active()
            ->when($v['query'] ?? null, fn ($q, $t) => $q->search($t))
            ->when($v['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->whereRaw('stock - reserved_stock > 0')
            ->limit($v['limit'] ?? 10)->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->price, 'category' => $p->category, 'available' => $p->availableStock()])
            ->all();
    }

    private function validateShipping(array $a, EnviaService $envia): array
    {
        $v = Validator::make($a, [
            'postal_code' => ['required', 'regex:/^[0-9]{6}$/'],
            'state' => 'required|in:' . implode(',', array_keys(AddressController::STATES)),
            'city' => 'required|string|max:100',
            'street' => 'required|string|max:160',
            'product_id' => 'nullable|integer|min:1',
            'quantity' => 'nullable|integer|min:1|max:100',
        ])->validate();

        $out = ['valid' => true, 'normalized' => [
            'postal_code' => $v['postal_code'], 'state' => $v['state'], 'state_name' => AddressController::STATES[$v['state']],
            'city' => trim($v['city']), 'street' => trim($v['street']),
        ]];

        if (!empty($v['product_id'])) {
            $product = Product::active()->findOrFail($v['product_id']);
            $item = (object) ['product' => $product, 'quantity' => $v['quantity'] ?? 1];
            $pkg = $envia->buildPackage([$item], (float) $product->price * $item->quantity);
            $out['rates'] = $envia->quote([
                'recipient' => 'Cliente', 'phone' => '', 'street' => $v['street'], 'number' => 'S/N', 'district' => '',
                'city' => $v['city'], 'state' => $v['state'], 'postal_code' => $v['postal_code'], 'country' => 'CO',
            ], $pkg);
        }

        return $out;
    }
}
