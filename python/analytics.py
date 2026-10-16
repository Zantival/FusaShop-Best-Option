#!/usr/bin/env python3
"""
FusaShop Analytics Module
Genera reportes y análisis de datos desde la BD MySQL.
Uso: python analytics.py [--report ventas|usuarios|productos] [--output json|csv]
"""

import sys
import json
import csv
import argparse
from datetime import datetime, timedelta

try:
    import mysql.connector
    MYSQL_AVAILABLE = True
except ImportError:
    MYSQL_AVAILABLE = False
    print("[WARN] mysql-connector-python no instalado. Usando datos demo.")

try:
    import pandas as pd
    PANDAS_AVAILABLE = True
except ImportError:
    PANDAS_AVAILABLE = False

# ── Config ──────────────────────────────────────────────────────────────────
DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "database": "fusashop",
    "user": "root",
    "password": "",
    "charset": "utf8mb4",
}

# ── DB helper ────────────────────────────────────────────────────────────────
def get_connection():
    if not MYSQL_AVAILABLE:
        return None
    try:
        return mysql.connector.connect(**DB_CONFIG)
    except Exception as e:
        print(f"[ERROR] DB connection failed: {e}", file=sys.stderr)
        return None

def query(sql, params=None):
    conn = get_connection()
    if not conn:
        return []
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute(sql, params or ())
        return cur.fetchall()
    except Exception as e:
        print(f"[ERROR] Query failed: {e}", file=sys.stderr)
        return []
    finally:
        conn.close()

# ── Reports ──────────────────────────────────────────────────────────────────
def report_ventas():
    rows = query("""
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') AS mes,
            COUNT(*) AS total_pedidos,
            SUM(total) AS ingresos,
            AVG(total) AS ticket_promedio,
            SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS ingresos_confirmados
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY mes
        ORDER BY mes
    """)
    if not rows:
        # Demo data
        rows = [
            {"mes": "2025-01", "total_pedidos": 12, "ingresos": 1450000, "ticket_promedio": 120833, "ingresos_confirmados": 1350000},
            {"mes": "2025-02", "total_pedidos": 18, "ingresos": 2100000, "ticket_promedio": 116666, "ingresos_confirmados": 2100000},
            {"mes": "2025-03", "total_pedidos": 25, "ingresos": 3200000, "ticket_promedio": 128000, "ingresos_confirmados": 3200000},
            {"mes": "2025-04", "total_pedidos": 31, "ingresos": 4800000, "ticket_promedio": 154838, "ingresos_confirmados": 4650000},
        ]
    return {"reporte": "ventas_mensuales", "generado": datetime.now().isoformat(), "datos": rows}

def report_usuarios():
    rows = query("""
        SELECT
            role,
            COUNT(*) AS total,
            MIN(created_at) AS primer_registro,
            MAX(created_at) AS ultimo_registro
        FROM users
        GROUP BY role
    """)
    crecimiento = query("""
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') AS mes,
            role,
            COUNT(*) AS nuevos
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY mes, role
        ORDER BY mes
    """)
    if not rows:
        rows = [
            {"role": "consumer", "total": 45, "primer_registro": "2025-01-05", "ultimo_registro": "2025-04-13"},
            {"role": "merchant", "total": 8, "primer_registro": "2025-01-10", "ultimo_registro": "2025-04-10"},
            {"role": "analyst", "total": 2, "primer_registro": "2025-01-01", "ultimo_registro": "2025-01-01"},
        ]
    return {"reporte": "usuarios", "generado": datetime.now().isoformat(), "resumen": rows, "crecimiento": crecimiento}

def report_productos():
    rows = query("""
        SELECT
            p.category,
            COUNT(DISTINCT p.id) AS total_productos,
            SUM(oi.quantity) AS unidades_vendidas,
            SUM(oi.quantity * oi.price) AS ingresos_categoria,
            AVG(p.price) AS precio_promedio
        FROM products p
        LEFT JOIN order_items oi ON oi.product_id = p.id
        WHERE p.is_active = 1
        GROUP BY p.category
        ORDER BY ingresos_categoria DESC
    """)
    top = query("""
        SELECT
            p.name,
            p.category,
            SUM(oi.quantity) AS vendidos,
            SUM(oi.quantity * oi.price) AS ingresos,
            p.stock AS stock_actual
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        GROUP BY p.id
        ORDER BY vendidos DESC
        LIMIT 10
    """)
    if not rows:
        rows = [
            {"category": "Electrónica", "total_productos": 4, "unidades_vendidas": 120, "ingresos_categoria": 9800000, "precio_promedio": 80750},
            {"category": "Ropa", "total_productos": 3, "unidades_vendidas": 85, "ingresos_categoria": 4250000, "precio_promedio": 83333},
            {"category": "Deportes", "total_productos": 1, "unidades_vendidas": 40, "ingresos_categoria": 2600000, "precio_promedio": 65000},
            {"category": "Alimentos", "total_productos": 1, "unidades_vendidas": 200, "ingresos_categoria": 7000000, "precio_promedio": 35000},
            {"category": "Hogar", "total_productos": 1, "unidades_vendidas": 25, "ingresos_categoria": 4500000, "precio_promedio": 180000},
        ]
    return {"reporte": "productos", "generado": datetime.now().isoformat(), "por_categoria": rows, "top_10": top}

def report_full():
    return {
        "fusashop_analytics": {
            "version": "1.0",
            "generado": datetime.now().isoformat(),
            "ventas": report_ventas(),
            "usuarios": report_usuarios(),
            "productos": report_productos(),
        }
    }

# ── Output ───────────────────────────────────────────────────────────────────
def output_json(data):
    print(json.dumps(data, ensure_ascii=False, indent=2, default=str))

def output_csv(data, report_name):
    datos = data.get("datos") or data.get("resumen") or data.get("por_categoria") or []
    if not datos:
        print("Sin datos para exportar en CSV.")
        return
    writer = csv.DictWriter(sys.stdout, fieldnames=datos[0].keys())
    writer.writeheader()
    writer.writerows(datos)

# ── CLI ──────────────────────────────────────────────────────────────────────
def main():
    parser = argparse.ArgumentParser(description="FusaShop Analytics")
    parser.add_argument("--report", choices=["ventas","usuarios","productos","all"], default="all")
    parser.add_argument("--output", choices=["json","csv"], default="json")
    parser.add_argument("--host", default=DB_CONFIG["host"])
    parser.add_argument("--db", default=DB_CONFIG["database"])
    parser.add_argument("--user", default=DB_CONFIG["user"])
    parser.add_argument("--password", default=DB_CONFIG["password"])
    args = parser.parse_args()

    DB_CONFIG["host"] = args.host
    DB_CONFIG["database"] = args.db
    DB_CONFIG["user"] = args.user
    DB_CONFIG["password"] = args.password

    reporters = {
        "ventas": report_ventas,
        "usuarios": report_usuarios,
        "productos": report_productos,
        "all": report_full,
    }
    data = reporters[args.report]()

    if args.output == "json":
        output_json(data)
    else:
        output_csv(data, args.report)

if __name__ == "__main__":
    main()
