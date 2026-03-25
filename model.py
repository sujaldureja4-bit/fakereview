import pandas as pd
import string
import pickle
import nltk
from nltk.corpus import stopwords
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.model_selection import train_test_split
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score

# Download stopwords
nltk.download('stopwords')

# ✅ LOAD DATA (AUTO HANDLE CSV / TSV)
try:
    df = pd.read_csv("reviews.csv")
except:
    df = pd.read_csv("reviews.csv", sep="\t")

# ✅ CLEAN COLUMN NAMES
df.columns = df.columns.str.strip().str.lower()

print("Original Columns:", list(df.columns))

# ✅ FORCE CORRECT COLUMN NAMES
review_col = None
label_col = None

for col in df.columns:
    if "review" in col:
        review_col = col
    if "label" in col:
        label_col = col

if review_col is None:
    review_col = df.columns[0]

if label_col is None:
    label_col = df.columns[-1]

df.rename(columns={
    review_col: "review",
    label_col: "label"
}, inplace=True)

print("Fixed Columns:", list(df.columns))
print(df.head())

# ✅ REMOVE NULL VALUES
df = df.dropna()

# ⚡ LIMIT DATA (REMOVE LATER IF YOU WANT FULL TRAINING)
df = df.head(2000)

# ✅ LOAD STOPWORDS ONCE (IMPORTANT FIX)
stop_words = set(stopwords.words("english"))

# ✅ CLEAN TEXT FUNCTION (FAST VERSION)
def clean_text(text):
    text = str(text).lower()
    text = "".join([c for c in text if c not in string.punctuation])
    words = text.split()
    words = [word for word in words if word not in stop_words]
    return " ".join(words)

print("Starting cleaning...")
df["cleaned"] = df["review"].apply(clean_text)
print("Cleaning done!")

# ✅ FEATURES
vectorizer = TfidfVectorizer()
X = vectorizer.fit_transform(df["cleaned"])
y = df["label"]

# ✅ TRAIN MODEL
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2)

model = LogisticRegression(max_iter=200)  # improved convergence
model.fit(X_train, y_train)

# ✅ EVALUATION
pred = model.predict(X_test)
print("Accuracy:", accuracy_score(y_test, pred))

# ✅ SAVE MODEL
pickle.dump(model, open("model.pkl", "wb"))
pickle.dump(vectorizer, open("vectorizer.pkl", "wb"))

# ✅ PREDICTION FUNCTION
def predict_review(review):
    cleaned = clean_text(review)
    vector = vectorizer.transform([cleaned])
    result = model.predict(vector)[0]
    return "✅ Genuine Review" if result == 1 else "❌ Fake Review"

# ✅ TEST
print(predict_review("This company is amazing and best service ever"))