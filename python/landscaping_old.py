import sys
import json
import pandas as pd
import pickle
import os

# Load model
model_path = "./models/landscaping_model.pkl"
model = pickle.load(open(model_path, 'rb'))

# Read JSON from command line
input_json = sys.argv[1]
data = json.loads(input_json)

# Convert to DataFrame
input_encoded = pd.DataFrame([data])

# Align features
missing_cols = [col for col in model.feature_names_ if col not in input_encoded.columns]
for col in missing_cols:
    input_encoded[col] = 0
input_encoded = input_encoded[model.feature_names_]

# Predict
prediction = model.predict(input_encoded)

# Output
print(json.dumps({'prediction': prediction[0]}))
