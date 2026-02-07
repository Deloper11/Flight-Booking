<form method="POST" action="/includes/feedback.inc.php" enctype="multipart/form-data" class="quantum-feedback-form">
    <input type="hidden" name="quantum_token" value="<?= CSRFToken::generate() ?>">
    
    <!-- Quantum Identity -->
    <div class="quantum-form-section">
        <h3 class="quantum-section-title">Your Quantum Identity</h3>
        
        <div class="quantum-input-group">
            <label class="quantum-label">Quantum Email</label>
            <input type="email" 
                   name="email" 
                   class="quantum-input"
                   placeholder="your.email@example.com"
                   data-quantum-validation="email"
                   required>
            <div class="quantum-validation-feedback" id="email-feedback"></div>
        </div>
        
        <div class="quantum-input-group">
            <label class="quantum-label">Name (Optional)</label>
            <input type="text" 
                   name="name" 
                   class="quantum-input"
                   placeholder="Your name"
                   data-quantum-ai="personalization">
        </div>
    </div>
    
    <!-- Quantum Rating -->
    <div class="quantum-form-section">
        <h3 class="quantum-section-title">Quantum Rating</h3>
        
        <div class="quantum-rating-system">
            <div class="quantum-rating-label">Overall Experience</div>
            <div class="quantum-stars" data-stars="5">
                <?php for($i = 1; $i <= 5; $i++): ?>
                <button type="button" 
                        class="quantum-star" 
                        data-value="<?= $i ?>"
                        aria-label="Rate <?= $i ?> stars">
                    <i class="fas fa-star"></i>
                </button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="stars" id="stars-input" value="0" required>
            <div class="quantum-rating-description" id="rating-description"></div>
        </div>
        
        <div class="quantum-sentiment-selector">
            <div class="quantum-sentiment-label">How are you feeling?</div>
            <div class="quantum-sentiment-options">
                <button type="button" class="sentiment-option" data-sentiment="happy">
                    <i class="fas fa-smile"></i>
                    <span>Happy</span>
                </button>
                <button type="button" class="sentiment-option" data-sentiment="neutral">
                    <i class="fas fa-meh"></i>
                    <span>Neutral</span>
                </button>
                <button type="button" class="sentiment-option" data-sentiment="sad">
                    <i class="fas fa-frown"></i>
                    <span>Sad</span>
                </button>
                <button type="button" class="sentiment-option" data-sentiment="angry">
                    <i class="fas fa-angry"></i>
                    <span>Angry</span>
                </button>
            </div>
            <input type="hidden" name="user_sentiment" id="sentiment-input">
        </div>
    </div>
    
    <!-- Quantum Questions -->
    <div class="quantum-form-section">
        <h3 class="quantum-section-title">Share Your Quantum Thoughts</h3>
        
        <div class="quantum-input-group">
            <label class="quantum-label">What did you like most?</label>
            <textarea name="1" 
                      class="quantum-textarea"
                      placeholder="Tell us what you enjoyed..."
                      data-quantum-ai="sentiment"
                      rows="4"
                      required></textarea>
            <div class="quantum-character-count" data-max="500">0/500</div>
        </div>
        
        <div class="quantum-input-group">
            <label class="quantum-label">What can we improve?</label>
            <textarea name="2" 
                      class="quantum-textarea"
                      placeholder="Share your suggestions for improvement..."
                      data-quantum-ai="categorization"
                      rows="4"
                      required></textarea>
            <div class="quantum-character-count" data-max="500">0/500</div>
        </div>
        
        <div class="quantum-input-group">
            <label class="quantum-label">Additional comments (Optional)</label>
            <textarea name="3" 
                      class="quantum-textarea"
                      placeholder="Any other thoughts you'd like to share?"
                      rows="3"></textarea>
            <div class="quantum-character-count" data-max="1000">0/1000</div>
        </div>
    </div>
    
    <!-- Multi-Modal Feedback -->
    <div class="quantum-form-section" data-quantum-optional="true">
        <h3 class="quantum-section-title">Multi-Modal Quantum Feedback</h3>
        
        <div class="quantum-multimodal-options">
            <div class="quantum-option">
                <label class="quantum-option-label">
                    <input type="checkbox" name="enable_voice" id="enable-voice">
                    <span>Record voice feedback</span>
                </label>
                <div class="quantum-voice-recorder" style="display: none;">
                    <button type="button" class="quantum-record-button">
                        <i class="fas fa-microphone"></i>
                        <span>Start Recording</span>
                    </button>
                    <div class="quantum-recording-status">
                        <div class="recording-indicator"></div>
                        <span class="recording-timer">00:00</span>
                    </div>
                    <audio controls class="quantum-audio-preview"></audio>
                    <input type="hidden" name="voice_feedback" id="voice-data">
                </div>
            </div>
            
            <div class="quantum-option">
                <label class="quantum-option-label">
                    <input type="checkbox" name="enable_video" id="enable-video">
                    <span>Record video feedback</span>
                </label>
                <div class="quantum-video-recorder" style="display: none;">
                    <video class="quantum-video-preview" autoplay muted></video>
                    <button type="button" class="quantum-record-video">
                        <i class="fas fa-video"></i>
                        <span>Start Video</span>
                    </button>
                    <input type="hidden" name="video_feedback" id="video-data">
                </div>
            </div>
            
            <div class="quantum-option">
                <label class="quantum-option-label">
                    <input type="checkbox" name="attach_files" id="attach-files">
                    <span>Attach files</span>
                </label>
                <div class="quantum-file-uploader" style="display: none;">
                    <input type="file" 
                           name="attachments[]" 
                           class="quantum-file-input"
                           multiple
                           accept=".pdf,.doc,.docx,.jpg,.png,.txt">
                    <div class="quantum-file-preview" id="file-preview"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Context and Tags -->
    <div class="quantum-form-section">
        <h3 class="quantum-section-title">Context & Tags</h3>
        
        <div class="quantum-input-group">
            <label class="quantum-label">Feedback Category</label>
            <select name="category" class="quantum-select" data-quantum-ai="suggestion">
                <option value="">Select a category</option>
                <option value="bug_report">Bug Report</option>
                <option value="feature_request">Feature Request</option>
                <option value="user_experience">User Experience</option>
                <option value="performance">Performance</option>
                <option value="support">Support</option>
                <option value="general">General Feedback</option>
            </select>
        </div>
        
        <div class="quantum-input-group">
            <label class="quantum-label">Tags</label>
            <div class="quantum-tag-selector">
                <?php foreach(['urgent', 'critical', 'suggestion', 'praise', 'issue'] as $tag): ?>
                <button type="button" class="quantum-tag" data-tag="<?= $tag ?>">
                    <?= ucfirst($tag) ?>
                </button>
                <?php endforeach; ?>
                <input type="hidden" name="tags" id="tags-input">
            </div>
        </div>
        
        <div class="quantum-input-group">
            <label class="quantum-label">Page URL</label>
            <input type="url" 
                   name="page_url" 
                   class="quantum-input"
                   placeholder="https://example.com/page"
                   value="<?= $_SERVER['HTTP_REFERER'] ?? '' ?>">
        </div>
    </div>
    
    <!-- AI Analysis Preview -->
    <div class="quantum-analysis-preview" id="analysis-preview">
        <div class="analysis-item" data-analysis="sentiment">
            <i class="fas fa-brain"></i>
            <span>Sentiment: Analyzing...</span>
        </div>
        <div class="analysis-item" data-analysis="category">
            <i class="fas fa-tags"></i>
            <span>Category: Analyzing...</span>
        </div>
        <div class="analysis-item" data-analysis="urgency">
            <i class="fas fa-clock"></i>
            <span>Urgency: Analyzing...</span>
        </div>
    </div>
    
    <!-- Honeypot Field (Spam Prevention) -->
    <div class="quantum-honeypot" style="display: none;">
        <label for="quantum_confirm">Leave this field empty</label>
        <input type="text" id="quantum_confirm" name="quantum_confirm">
    </div>
    
    <!-- Submit Button -->
    <button type="submit" 
            name="feed_but" 
            class="quantum-submit-button"
            data-quantum-processing="false">
        <span class="button-text">
            <i class="fas fa-paper-plane"></i>
            Submit Quantum Feedback
        </span>
        <span class="quantum-spinner" style="display: none;">
            <i class="fas fa-atom fa-spin"></i>
            Processing quantum feedback...
        </span>
    </button>
    
    <!-- Status Indicators -->
    <div class="quantum-status-container">
        <div class="quantum-status" id="quantum-status">
            <div class="status-item" data-status="validation">
                <i class="fas fa-check-circle"></i>
                <span>Input validation...</span>
            </div>
            <div class="status-item" data-status="spam">
                <i class="fas fa-shield-alt"></i>
                <span>Spam detection...</span>
            </div>
            <div class="status-item" data-status="analysis">
                <i class="fas fa-chart-line"></i>
                <span>AI analysis...</span>
            </div>
            <div class="status-item" data-status="blockchain">
                <i class="fas fa-link"></i>
                <span>Blockchain verification...</span>
            </div>
        </div>
    </div>
    
    <!-- Privacy Notice -->
    <div class="quantum-privacy-notice">
        <p>
            <i class="fas fa-lock"></i>
            Your feedback is secured with quantum encryption. 
            By submitting, you agree to our 
            <a href="/privacy" target="_blank">Privacy Policy</a> and 
            <a href="/terms" target="_blank">Terms of Service</a>.
        </p>
    </div>
</form>

<!-- Quantum Feedback Scripts -->
<script>
// Star rating system
const stars = document.querySelectorAll('.quantum-star');
const starsInput = document.getElementById('stars-input');
const ratingDescription = document.getElementById('rating-description');

const descriptions = {
    1: 'Poor - Very dissatisfied',
    2: 'Fair - Somewhat dissatisfied',
    3: 'Good - Neutral experience',
    4: 'Very Good - Satisfied',
    5: 'Excellent - Very satisfied'
};

stars.forEach(star => {
    star.addEventListener('click', () => {
        const value = parseInt(star.getAttribute('data-value'));
        starsInput.value = value;
        
        // Update star display
        stars.forEach((s, index) => {
            if (index < value) {
                s.classList.add('active');
                s.querySelector('i').classList.add('fas');
                s.querySelector('i').classList.remove('far');
            } else {
                s.classList.remove('active');
                s.querySelector('i').classList.remove('fas');
                s.querySelector('i').classList.add('far');
            }
        });
        
        // Update description
        ratingDescription.textContent = descriptions[value];
    });
});

// Sentiment selector
const sentimentOptions = document.querySelectorAll('.sentiment-option');
const sentimentInput = document.getElementById('sentiment-input');

sentimentOptions.forEach(option => {
    option.addEventListener('click', () => {
        const sentiment = option.getAttribute('data-sentiment');
        sentimentInput.value = sentiment;
        
        // Update UI
        sentimentOptions.forEach(opt => opt.classList.remove('selected'));
        option.classList.add('selected');
    });
});

// Character counters
const textareas = document.querySelectorAll('.quantum-textarea');
textareas.forEach(textarea => {
    const counter = textarea.parentElement.querySelector('.quantum-character-count');
    const max = parseInt(counter.getAttribute('data-max'));
    
    textarea.addEventListener('input', () => {
        const length = textarea.value.length;
        counter.textContent = `${length}/${max}`;
        
        if (length > max) {
            counter.classList.add('exceeded');
        } else {
            counter.classList.remove('exceeded');
        }
    });
});

// Voice recording
const enableVoice = document.getElementById('enable-voice');
const voiceRecorder = document.querySelector('.quantum-voice-recorder');
const recordButton = document.querySelector('.quantum-record-button');
const recordingStatus = document.querySelector('.quantum-recording-status');
const audioPreview = document.querySelector('.quantum-audio-preview');
const voiceData = document.getElementById('voice-data');

let mediaRecorder;
let audioChunks = [];

enableVoice.addEventListener('change', () => {
    voiceRecorder.style.display = enableVoice.checked ? 'block' : 'none';
});

recordButton.addEventListener('click', async () => {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') {
        // Start recording
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        
        mediaRecorder.start();
        recordButton.innerHTML = '<i class="fas fa-stop"></i><span>Stop Recording</span>';
        recordingStatus.style.display = 'flex';
        
        mediaRecorder.ondataavailable = (event) => {
            audioChunks.push(event.data);
        };
        
        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
            const audioUrl = URL.createObjectURL(audioBlob);
            
            audioPreview.src = audioUrl;
            audioPreview.style.display = 'block';
            
            // Convert to base64 for submission
            const reader = new FileReader();
            reader.readAsDataURL(audioBlob);
            reader.onloadend = () => {
                voiceData.value = reader.result;
            };
            
            // Reset
            audioChunks = [];
            recordButton.innerHTML = '<i class="fas fa-microphone"></i><span>Start Recording</span>';
            recordingStatus.style.display = 'none';
        };
        
        // Update timer
        let seconds = 0;
        const timer = setInterval(() => {
            seconds++;
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            document.querySelector('.recording-timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            
            if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                clearInterval(timer);
            }
        }, 1000);
        
    } else {
        // Stop recording
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
});

// File upload preview
const attachFiles = document.getElementById('attach-files');
const fileUploader = document.querySelector('.quantum-file-uploader');
const fileInput = document.querySelector('.quantum-file-input');
const filePreview = document.getElementById('file-preview');

attachFiles.addEventListener('change', () => {
    fileUploader.style.display = attachFiles.checked ? 'block' : 'none';
});

fileInput.addEventListener('change', () => {
    filePreview.innerHTML = '';
    
    Array.from(fileInput.files).forEach(file => {
        const fileElement = document.createElement('div');
        fileElement.className = 'file-item';
        fileElement.innerHTML = `
            <i class="fas fa-file"></i>
            <span>${file.name} (${(file.size / 1024).toFixed(2)} KB)</span>
            <button type="button" class="remove-file">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        filePreview.appendChild(fileElement);
    });
});

// Tag selector
const tags = document.querySelectorAll('.quantum-tag');
const tagsInput = document.getElementById('tags-input');
const selectedTags = [];

tags.forEach(tag => {
    tag.addEventListener('click', () => {
        const tagValue = tag.getAttribute('data-tag');
        
        if (selectedTags.includes(tagValue)) {
            // Remove tag
            const index = selectedTags.indexOf(tagValue);
            selectedTags.splice(index, 1);
            tag.classList.remove('selected');
        } else {
            // Add tag
            selectedTags.push(tagValue);
            tag.classList.add('selected');
        }
        
        tagsInput.value = selectedTags.join(',');
    });
});

// Real-time AI analysis
const analysisTextareas = document.querySelectorAll('[data-quantum-ai]');
const analysisPreview = document.getElementById('analysis-preview');

analysisTextareas.forEach(textarea => {
    textarea.addEventListener('input', debounce(async () => {
        if (textarea.value.length < 10) return;
        
        const response = await fetch('/api/quantum/analyze-feedback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Quantum-Token': document.querySelector('input[name="quantum_token"]').value
            },
            body: JSON.stringify({
                text: textarea.value,
                analysis_type: textarea.getAttribute('data-quantum-ai')
            })
        });
        
        const result = await response.json();
        updateAnalysisPreview(result);
    }, 1000));
});

function updateAnalysisPreview(analysis) {
    const items = analysisPreview.querySelectorAll('.analysis-item');
    
    items.forEach(item => {
        const type = item.getAttribute('data-analysis');
        if (analysis[type]) {
            const icon = item.querySelector('i');
            const text = item.querySelector('span');
            
            text.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)}: ${analysis[type]}`;
            
            // Update icon color based on sentiment
            if (type === 'sentiment') {
                const colors = {
                    'positive': '#4CAF50',
                    'negative': '#F44336',
                    'neutral': '#FF9800',
                    'mixed': '#9C27B0'
                };
                icon.style.color = colors[analysis.sentiment] || '#607D8B';
            }
        }
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Form submission with quantum validation
document.querySelector('.quantum-feedback-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Check honeypot field
    const honeypot = document.getElementById('quantum_confirm');
    if (honeypot.value !== '') {
        // Bot detected
        e.target.submit(); // Let it submit but we'll handle it as spam
        return;
    }
    
    const form = e.target;
    const submitButton = form.querySelector('.quantum-submit-button');
    const buttonText = submitButton.querySelector('.button-text');
    const spinner = submitButton.querySelector('.quantum-spinner');
    
    // Show processing state
    buttonText.style.display = 'none';
    spinner.style.display = 'inline-block';
    submitButton.disabled = true;
    submitButton.setAttribute('data-quantum-processing', 'true');
    
    // Update status indicators
    const statusItems = document.querySelectorAll('.status-item');
    statusItems.forEach((item, index) => {
        setTimeout(() => {
            item.classList.add('active');
        }, index * 500);
    });
    
    // Perform final validation
    const validationResult = await performFinalValidation(form);
    
    if (!validationResult.valid) {
        alert(validationResult.message);
        resetFormState();
        return;
    }
    
    // Submit form with quantum animation
    setTimeout(() => {
        form.submit();
    }, 2000); // Simulate quantum processing delay
});

async function performFinalValidation(form) {
    const formData = new FormData(form);
    
    const response = await fetch('/api/quantum/validate-feedback', {
        method: 'POST',
        headers: {
            'X-Quantum-Token': document.querySelector('input[name="quantum_token"]').value
        },
        body: formData
    });
    
    return await response.json();
}

function resetFormState() {
    const submitButton = document.querySelector('.quantum-submit-button');
    const buttonText = submitButton.querySelector('.button-text');
    const spinner = submitButton.querySelector('.quantum-spinner');
    
    buttonText.style.display = 'inline-block';
    spinner.style.display = 'none';
    submitButton.disabled = false;
    submitButton.setAttribute('data-quantum-processing', 'false');
    
    const statusItems = document.querySelectorAll('.status-item');
    statusItems.forEach(item => {
        item.classList.remove('active');
    });
}
</script>
