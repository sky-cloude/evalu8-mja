from flask import Flask, request, jsonify
from transformers import AutoTokenizer, AutoModelForSequenceClassification, pipeline

app = Flask(__name__)

# Load the tokenizer and model for sentiment analysis
model_name = "cardiffnlp/twitter-xlm-roberta-base-sentiment"
tokenizer = AutoTokenizer.from_pretrained(model_name)
model = AutoModelForSequenceClassification.from_pretrained(model_name)

# Initialize the sentiment analysis pipeline
sentiment_analyzer = pipeline("sentiment-analysis", model=model, tokenizer=tokenizer)

# API route for analyzing a batch of texts
@app.route('/analyze_batch', methods=['POST'])
def analyze_sentiment_batch():
    data = request.json
    texts = data.get('texts', [])

    if not texts:
        return jsonify({"error": "No texts provided"}), 400

    # Perform sentiment analysis on each text in the batch
    results = []
    for text in texts:
        analysis = sentiment_analyzer(text)[0]
        label = analysis['label'].capitalize()
        score = analysis['score']

        # Convert the label into a sentiment type (0 = negative, 1 = neutral, 2 = positive)
        if label == "Negative":
            sentiment_type = 0
        elif label == "Neutral":
            sentiment_type = 1
        elif label == "Positive":
            sentiment_type = 2
        else:
            sentiment_type = None  # Handle unexpected labels

        results.append({
            "text": text,
            "label": label,
            "score": score,
            "sentiment_type": sentiment_type
        })

    # Return the results as JSON
    return jsonify({"results": results})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)