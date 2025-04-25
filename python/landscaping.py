import sys
import json
import pandas as pd
import pickle
import os

model_path = os.path.join(os.path.dirname(__file__), './model/landscapping_model.pkl')
model = pickle.load(open(model_path, 'rb'))

def main():
    data = json.loads(sys.argv[1])
    input_encoded = pd.DataFrame([data])

    missing_cols = [col for col in model.feature_names_ if col not in input_encoded.columns]
    for col in missing_cols:
        input_encoded[col] = 0
    input_encoded = input_encoded[model.feature_names_]

    prediction = model.predict(input_encoded)
    print(json.dumps({"success": True, "prediction": prediction[0]}))

if __name__ == "__main__":
    main()
