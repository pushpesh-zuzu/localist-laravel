import sys
import subprocess
import importlib

# List of required packages
required_packages = ['pandas', 'pickle', 'os', 'json']

# Packages that need pip installation (built-in ones like os, json, and pickle don't need to be installed)
installable_packages = ['pandas']

def install_missing_packages():
    for package in installable_packages:
        try:
            importlib.import_module(package)
        except ImportError:
            print(f"Installing missing package: {package}")
            subprocess.check_call([sys.executable, "-m", "pip", "install", package])

# Install necessary packages
install_missing_packages()



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
