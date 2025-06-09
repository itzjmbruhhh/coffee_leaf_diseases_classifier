from tensorflow.keras.models import load_model
import os

from fastapi import FastAPI, UploadFile, HTTPException
import uvicorn
import uuid

from utils import generate_heatmap, coffee_or_not, predict_image, read_file_as_image

# Initialize FastAPI app
app = FastAPI()

# Load pre-trained models
main_model_path = "api/model.keras"
second_model_path = "api/coffee_or_not.keras"
MAIN_MODEL = load_model(main_model_path)        # Model to classify coffee leaf diseases
SECOND_MODEL = load_model(second_model_path)    # Model to verify if image is of a coffee leaf

# Define class labels for each model
CLASS_NAMES = ['Cercospora', 'Healthy', 'Miner', 'Phoma', 'Rust']         # Labels for MAIN_MODEL
CLASS_NAMES2 = ["Coffee", "Not Coffee"]                                   # Labels for SECOND_MODEL

@app.post("/predict")
async def predict(file: UploadFile):
    """
    FastAPI endpoint for image prediction.
    Steps:
    1. Reads uploaded image.
    2. Checks if it's likely a coffee leaf using SECOND_MODEL.
    3. If valid, uses MAIN_MODEL to classify the disease.
    4. Rejects low-confidence predictions.
    5. Generates and returns a heatmap for visualization.
    """
    # Read and process uploaded image
    image_bytes = await file.read()
    image = read_file_as_image(image_bytes)  # Convert bytes to PIL image or appropriate format

    # Use SECOND_MODEL to check if the uploaded image is a coffee leaf
    if not coffee_or_not(SECOND_MODEL, image, CLASS_NAMES2):
        raise HTTPException(status_code=400, detail="Image is unlikely to be a coffee leaf.")

    # Save the uploaded image temporarily (required for heatmap generation)
    temp_input_path = f"temp_{uuid.uuid4().hex}.jpg"
    image.save(temp_input_path)

    # Use MAIN_MODEL to predict the disease
    prediction, confidence, all_probs = predict_image(MAIN_MODEL, image, CLASS_NAMES)

    # Reject predictions with low confidence (below 70%)
    if confidence < 75.0:
        os.remove(temp_input_path)  # Clean up temporary file
        raise HTTPException(status_code=422, detail="Failed to classify image.")

    # Generate a heatmap to visualize the area of interest in the prediction
    heatmap_path = generate_heatmap(temp_input_path, MAIN_MODEL)

    # Clean up temporary image file after heatmap generation
    os.remove(temp_input_path)

    # Return prediction results
    return {
        "prediction": prediction,                       # Predicted disease label
        "confidence": round(float(confidence)),         # Confidence percentage
        "probabilities": all_probs,                     # All class probabilities
        "heatmap_path": heatmap_path                    # Path to generated heatmap image
    }

# Start the FastAPI server if this script is run directly
if __name__ == "__main__":
    uvicorn.run(app, host='localhost', port=8000)
