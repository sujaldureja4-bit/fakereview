from flask import Flask, request, render_template, jsonify
import pickle
import string
import nltk
from nltk.corpus import stopwords
import os

app = Flask(__name__)

# ✅ Load model files
model = pickle.load(open("model.pkl", "rb"))
vectorizer = pickle.load(open("vectorizer.pkl", "rb"))

# ✅ Handle NLTK stopwords safely (important for Render)
try:
    stop_words = set(stopwords.words("english"))
except:
    nltk.download('stopwords')
    stop_words = set(stopwords.words("english"))

# ✅ Clean text function
def clean_text(text):
    text = str(text).lower()
    text = "".join([c for c in text if c not in string.punctuation])
    words = text.split()
    words = [word for word in words if word not in stop_words]
    return " ".join(words)

# ✅ Home route
@app.route("/", methods=["GET"])
def home():
    return render_template("index.html")

# ✅ Prediction route
@app.route("/predict", methods=["POST"])
def predict():
    review = request.form["review"]
    cleaned = clean_text(review)
    vector = vectorizer.transform([cleaned])
    prediction = model.predict(vector)[0]

    result = "✅ Genuine Review" if prediction == 1 else "❌ Fake Review"

    return jsonify({"result": result})

# ✅ IMPORTANT for Render deployment
if __name__ == "__main__":
    port = int(os.environ.get("PORT", 10000))
    app.run(host="0.0.0.0", port=port)
