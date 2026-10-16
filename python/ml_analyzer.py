import sys
import json

def analyze(data):
    if not data:
        return {"status": "error", "message": "No data provided"}
    
    # Ejemplo de procesamiento (mock-up de "Machine Learning" de ejemplo)
    # Imaginemos que evalúa si una empresa tiene anomalías o clasifica su rendimiento.
    results = []
    for item in data:
        ventas = item.get("total_sales", 0)
        rating = item.get("avg_rating", 0)
        
        # Un score dummy de calidad/riesgo
        score = (ventas * 0.4) + (rating * 0.6 * 10)
        
        classification = "Low Performer"
        if score > 80:
            classification = "Top Performer"
        elif score > 40:
            classification = "Average Performer"
            
        results.append({
            "merchant_id": item.get("merchant_id"),
            "company_name": item.get("company_name"),
            "raw_score": round(score, 2),
            "classification": classification,
            "anomaly_detected": ventas > 1000 and rating < 2.0
        })
        
    return {"status": "success", "analytics": results}

if __name__ == "__main__":
    try:
        input_data = sys.stdin.read()
        parsed_data = json.loads(input_data)
        output = analyze(parsed_data)
        print(json.dumps(output))
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
