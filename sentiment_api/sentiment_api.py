from flask import Flask, request, jsonify
from flask_cors import CORS
from transformers import pipeline
import threading
import logging
import os
import sys
from functools import lru_cache
import time

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('sentiment_api.log'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)

# Global model variable
sentiment_analyzer = None
model_loaded = False
model_lock = threading.Lock()
model_loading = False

@lru_cache(maxsize=1)
def get_model():
    """Load and cache the sentiment analysis model"""
    logger.info("Loading sentiment analysis model...")
    try:
        # Try different models in order of preference
        models_to_try = [
            "tabularisai/multilingual-sentiment-analysis",
            "distilbert-base-uncased-finetuned-sst-2-english",
            "nlptown/bert-base-multilingual-uncased-sentiment"
        ]
        
        for model_name in models_to_try:
            try:
                logger.info(f"Attempting to load model: {model_name}")
                analyzer = pipeline(
                    "sentiment-analysis",
                    model=model_name,
                    return_all_scores=True
                )
                logger.info(f"Model {model_name} loaded successfully")
                return analyzer, model_name
            except Exception as e:
                logger.warning(f"Failed to load {model_name}: {e}")
                continue
        
        raise Exception("All models failed to load")
        
    except Exception as e:
        logger.error(f"Error loading all models: {e}")
        return None, None

def load_model():
    """Initialize the global model variable"""
    global sentiment_analyzer, model_loaded, model_loading
    
    with model_lock:
        if model_loaded or model_loading:
            return
            
        model_loading = True
        
    try:
        result = get_model()
        if result[0] is not None:
            with model_lock:
                sentiment_analyzer = result[0]
                model_loaded = True
                logger.info("Model successfully loaded and ready for use")
        else:
            logger.error("Failed to load any sentiment analysis model")
    except Exception as e:
        logger.error(f"Exception during model loading: {e}")
    finally:
        with model_lock:
            model_loading = False

def preprocess_text(text):
    """Clean and preprocess text for sentiment analysis"""
    if not text:
        return ""
    
    # Basic text cleaning
    text = str(text).strip()
    
    # Remove excessive whitespace
    text = ' '.join(text.split())
    
    # Limit text length to avoid API timeouts
    if len(text) > 512:
        text = text[:512]
    
    return text

def normalize_sentiment_result(result):
    """Normalize sentiment analysis results to consistent format"""
    scores = {
        'negative': 0.0,
        'neutral': 0.0,
        'positive': 0.0
    }
    
    try:
        logger.info(f"Raw model output: {result}")
        
        for item in result[0]:
            label = item['label'].lower()
            score = float(item['score'])
            
            # Map all negative variations to 'negative'
            if any(neg_term in label for neg_term in ['neg', 'very neg', 'extremely neg', '0', '1', 'label_0', 'label_1']):
                scores['negative'] += score
            
            # Map all neutral variations to 'neutral'
            elif any(neu_term in label for neu_term in ['neu', '2', '3', 'label_2', 'label_3']):
                scores['neutral'] += score
            
            # Map all positive variations to 'positive'
            elif any(pos_term in label for pos_term in ['pos', 'very pos', 'extremely pos', '4', '5', 'label_4', 'label_5']):
                scores['positive'] += score
            
            # Handle numeric labels from 1-5 scale
            elif label.isdigit():
                num = int(label)
                if num <= 2:
                    scores['negative'] += score
                elif num == 3:
                    scores['neutral'] += score
                else:
                    scores['positive'] += score
            else:
                # Fallback for unknown labels
                logger.warning(f"Unknown sentiment label: {label}")
                # Try to infer from the label text
                if 'neg' in label:
                    scores['negative'] += score
                elif 'neu' in label:
                    scores['neutral'] += score
                elif 'pos' in label:
                    scores['positive'] += score
                else:
                    # Default to proportional distribution
                    scores['negative'] += score * 0.33
                    scores['neutral'] += score * 0.33
                    scores['positive'] += score * 0.33
        
        # Determine primary sentiment based on aggregated scores
        primary_sentiment = max(scores, key=scores.get)
        confidence = scores[primary_sentiment]
        
        # Normalize scores to sum to 1 (optional, for consistency)
        total = sum(scores.values())
        if total > 0:
            for key in scores:
                scores[key] = scores[key] / total

        # Enhance sentiment label for very high or low confidence
        sentiment_label = primary_sentiment
        if primary_sentiment == 'positive' and confidence >= 0.85:
            sentiment_label = 'very positive'
        elif primary_sentiment == 'negative' and confidence >= 0.85:
            sentiment_label = 'very negative'
        
        return {
            'sentiment': primary_sentiment,  # This will be 'negative', 'neutral', or 'positive'
            'scores': scores,
            'confidence': round(confidence, 4),
            'original_sentiment': result[0][0]['label']  # Keep original for reference
        }
        
    except Exception as e:
        logger.error(f"Error normalizing sentiment result: {e}")
        return {
            'sentiment': 'unknown',
            'scores': {'negative': 0.0, 'neutral': 0.0, 'positive': 0.0},
            'confidence': 0.0
        }

@app.route('/analyze', methods=['POST'])
def analyze_sentiment():
    """Main sentiment analysis endpoint"""
    start_time = time.time()
    
    try:
        # Parse request
        data = request.get_json()
        
        if not data:
            return jsonify({
                'error': 'No JSON data provided',
                'sentiment': 'unknown'
            }), 400
            
        text = data.get('text', '')
        
        if not text:
            return jsonify({
                'error': 'No text provided',
                'sentiment': 'unknown'
            }), 400
        
        # Preprocess text
        clean_text = preprocess_text(text)
        
        if not clean_text:
            return jsonify({
                'error': 'Empty text after preprocessing',
                'sentiment': 'unknown'
            }), 400
        
        # Check if model is loaded
        if not model_loaded and not model_loading:
            logger.info("Model not loaded, attempting to load...")
            load_model()
        
        # Wait a bit if model is still loading
        wait_count = 0
        while model_loading and wait_count < 30:  # Wait up to 30 seconds
            time.sleep(1)
            wait_count += 1
        
        if not model_loaded:
            return jsonify({
                'error': 'Sentiment analysis model not available',
                'sentiment': 'unknown'
            }), 503
        
        # Perform sentiment analysis
        try:
            with model_lock:
                if sentiment_analyzer is None:
                    raise Exception("Model is None")
                result = sentiment_analyzer(clean_text)
                
        except Exception as e:
            logger.error(f"Model prediction error: {e}")
            return jsonify({
                'error': f'Prediction failed: {str(e)}',
                'sentiment': 'unknown'
            }), 500
        
        # Normalize and format response
        normalized_result = normalize_sentiment_result(result)
        
        # Add processing time
        processing_time = time.time() - start_time
        normalized_result['processing_time'] = round(processing_time, 3)
        normalized_result['text_length'] = len(clean_text)
        
        logger.info(f"Successfully analyzed sentiment for text length {len(clean_text)}: {normalized_result['sentiment']} (confidence: {normalized_result['confidence']:.3f})")
        
        return jsonify(normalized_result)
        
    except Exception as e:
        processing_time = time.time() - start_time
        logger.error(f"Unexpected error in analyze_sentiment after {processing_time:.3f}s: {e}")
        return jsonify({
            'error': f'Internal server error: {str(e)}',
            'sentiment': 'unknown',
            'processing_time': round(processing_time, 3)
        }), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'model_loaded': model_loaded,
        'model_loading': model_loading,
        'service': 'sentiment-analysis-api',
        'version': '1.0.0',
        'timestamp': int(time.time())
    })

@app.route('/test', methods=['GET'])
def test_endpoint():
    """Test endpoint with sample text"""
    sample_texts = [
        "I am very satisfied with the excellent service provided today!",
        "The service was okay, nothing special really.",
        "I am extremely disappointed with the poor quality of service and long waiting time."
    ]
    
    results = []
    
    for i, text in enumerate(sample_texts):
        try:
            if model_loaded:
                with model_lock:
                    if sentiment_analyzer is not None:
                        result = sentiment_analyzer(text)
                        normalized = normalize_sentiment_result(result)
                        results.append({
                            'test_id': i + 1,
                            'text': text,
                            'sentiment': normalized['sentiment'],
                            'confidence': normalized['confidence'],
                            'scores': normalized['scores'],
                            'original_sentiment': normalized.get('original_sentiment', 'unknown')
                        })
                    else:
                        results.append({
                            'test_id': i + 1,
                            'text': text,
                            'sentiment': 'unknown',
                            'error': 'Model is None'
                        })
            else:
                results.append({
                    'test_id': i + 1,
                    'text': text,
                    'sentiment': 'unknown',
                    'error': 'Model not loaded'
                })
        except Exception as e:
            results.append({
                'test_id': i + 1,
                'text': text,
                'sentiment': 'unknown',
                'error': str(e)
            })
    
    return jsonify({
        'test_results': results,
        'model_loaded': model_loaded,
        'model_loading': model_loading,
        'timestamp': int(time.time())
    })

@app.route('/status', methods=['GET'])
def status():
    """Detailed status endpoint"""
    return jsonify({
        'service': 'Sentiment Analysis API',
        'version': '1.0.0',
        'status': 'running',
        'model_loaded': model_loaded,
        'model_loading': model_loading,
        'endpoints': {
            '/analyze': 'POST - Analyze sentiment of text',
            '/health': 'GET - Basic health check',
            '/test': 'GET - Test with sample data',
            '/status': 'GET - Detailed status information'
        },
        'timestamp': int(time.time())
    })

@app.errorhandler(404)
def not_found(error):
    return jsonify({
        'error': 'Endpoint not found',
        'message': 'Please check the API documentation for available endpoints'
    }), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({
        'error': 'Internal server error',
        'message': 'An unexpected error occurred'
    }), 500

# Initialize model loading in background thread
def initialize_model():
    """Initialize model in background"""
    logger.info("Starting background model initialization...")
    load_model()

if __name__ == '__main__':
    # Start model loading in background
    model_thread = threading.Thread(target=initialize_model, daemon=True)
    model_thread.start()
    
    # Get port from environment variable or default to 5000
    port = int(os.environ.get('PORT', 5000))
    debug = os.environ.get('FLASK_DEBUG', 'False').lower() == 'true'
    
    logger.info(f"Starting Sentiment Analysis API on port {port}")
    logger.info(f"Debug mode: {debug}")
    
    app.run(
        host='0.0.0.0', 
        port=port, 
        debug=debug, 
        threaded=True,
        use_reloader=False
    )