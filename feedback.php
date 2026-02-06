<?php 
session_start();
include_once 'helpers/helper.php'; 
subview('header.php');

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting check (simplified version)
if (!isset($_SESSION['last_feedback_time'])) {
    $_SESSION['last_feedback_time'] = 0;
}
$time_since_last = time() - $_SESSION['last_feedback_time'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Share your feedback with AirTic 2026">
    <meta name="robots" content="noindex, nofollow">
    <title>Feedback - AirTic 2026</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --rating-color: #ffc107;
        --rating-hover: #ffd700;
    }
    
    @font-face {
        font-family: 'product sans';
        src: url('assets/css/Product Sans Bold.ttf');
    }
    
    body {
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                    url('assets/images/plane1.jpg') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
        padding: 20px;
    }
    
    .feedback-container {
        max-width: 800px;
        margin: 30px auto;
        animation: fadeIn 0.8s ease-out;
    }
    
    .feedback-card {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 25px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }
    
    .feedback-header {
        background: var(--primary-gradient);
        padding: 40px 30px;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .feedback-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 30px 30px;
        transform: rotate(15deg);
        opacity: 0.3;
    }
    
    h1.feedback-title {
        font-family: 'product sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 3rem !important;
        margin-bottom: 15px;
        font-weight: 800;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        position: relative;
        z-index: 2;
    }
    
    .feedback-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 0;
        position: relative;
        z-index: 2;
    }
    
    .feedback-body {
        padding: 40px;
    }
    
    .form-group-custom {
        margin-bottom: 2rem;
        position: relative;
    }
    
    .form-label {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1rem;
    }
    
    .form-label i {
        color: #667eea;
    }
    
    .form-input {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
        color: #2d3748;
    }
    
    .form-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .form-input::placeholder {
        color: #a0aec0;
    }
    
    textarea.form-input {
        min-height: 120px;
        resize: vertical;
        font-family: inherit;
    }
    
    .select-custom {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23667eea' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 16px;
        padding-right: 50px;
    }
    
    /* Enhanced Rating System */
    .rating-container {
        background: #f8fafc;
        padding: 30px;
        border-radius: 15px;
        border: 2px solid #e2e8f0;
        margin: 2rem 0;
    }
    
    .rating-title {
        text-align: center;
        margin-bottom: 25px;
        color: #2d3748;
        font-size: 1.3rem;
        font-weight: 600;
    }
    
    .rating-stars {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 15px;
        direction: rtl;
    }
    
    .rating-stars input {
        display: none;
    }
    
    .rating-stars label {
        cursor: pointer;
        width: 60px;
        height: 60px;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .rating-stars label i {
        font-size: 3.5rem;
        color: #e2e8f0;
        transition: all 0.3s ease;
    }
    
    .rating-stars label:hover i,
    .rating-stars label:hover ~ label i {
        color: var(--rating-hover);
        transform: scale(1.1);
    }
    
    .rating-stars input:checked ~ label i {
        color: var(--rating-color);
    }
    
    .rating-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        padding: 0 20px;
    }
    
    .rating-label {
        font-size: 0.9rem;
        color: #718096;
        text-align: center;
        flex: 1;
    }
    
    .rating-feedback {
        text-align: center;
        margin-top: 15px;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--rating-color);
        min-height: 30px;
    }
    
    .character-count {
        font-size: 0.85rem;
        color: #718096;
        text-align: right;
        margin-top: 5px;
    }
    
    .character-count.warning {
        color: #f59e0b;
    }
    
    .character-count.danger {
        color: #ef4444;
    }
    
    .submit-btn {
        background: var(--success-gradient);
        color: white;
        border: none;
        padding: 18px 50px;
        font-size: 1.2rem;
        font-weight: 600;
        border-radius: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: block;
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
    }
    
    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
    }
    
    .submit-btn:active {
        transform: translateY(-1px);
    }
    
    .submit-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .submit-btn i {
        margin-right: 10px;
    }
    
    .spinner-border {
        display: none;
        margin-right: 10px;
    }
    
    .alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    }
    
    .feedback-emoji {
        font-size: 4rem;
        margin-bottom: 20px;
        display: block;
    }
    
    .privacy-note {
        background: #f0f9ff;
        border-left: 4px solid #3b82f6;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        font-size: 0.9rem;
        color: #4b5563;
    }
    
    .privacy-note i {
        color: #3b82f6;
        margin-right: 8px;
    }
    
    .required-asterisk {
        color: #ef4444;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
    
    .pulse {
        animation: pulse 2s infinite;
    }
    
    @media (max-width: 768px) {
        .feedback-container {
            margin: 15px auto;
        }
        
        .feedback-body {
            padding: 25px;
        }
        
        h1.feedback-title {
            font-size: 2.2rem !important;
        }
        
        .rating-stars label {
            width: 45px;
            height: 45px;
        }
        
        .rating-stars label i {
            font-size: 2.5rem;
        }
        
        .feedback-header {
            padding: 30px 20px;
        }
        
        .submit-btn {
            padding: 15px 30px;
            font-size: 1.1rem;
        }
    }
    
    @media print {
        .feedback-card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
    </style>
</head>
<body>
    <div class="feedback-container">
        <?php
        // Display error/success messages
        if(isset($_GET['error'])) {
            echo '<div class="alert-container">';
            switch($_GET['error']) {
                case 'invalidemail':
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>Please enter a valid email address.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                    break;
                case 'sqlerror':
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-database me-2"></i>Database error. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                    break;
                case 'ratelimit':
                    $wait_time = 300 - $time_since_last; // 5 minutes
                    $wait_minutes = ceil($wait_time / 60);
                    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-clock me-2"></i>Please wait ' . $wait_minutes . ' minutes before submitting another feedback.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                    break;
                case 'emptysubmission':
                    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>Please fill in all required fields.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                    break;
            }
            echo '</div>';
        } 
        
        if(isset($_GET['success'])) {
            echo '<div class="alert-container">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Thank you for your valuable feedback!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                  </div>';
        }
        ?>
        
        <div class="feedback-card animate__animated animate__fadeInUp">
            <div class="feedback-header">
                <div class="feedback-emoji">✈️</div>
                <h1 class="feedback-title">Share Your Experience</h1>
                <p class="feedback-subtitle">Help us improve AirTic 2026 with your valuable feedback</p>
            </div>
            
            <div class="feedback-body">
                <form action="includes/feedback.inc.php" method="POST" id="feedbackForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="user_agent" value="<?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?>">
                    <input type="hidden" name="ip_address" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
                    
                    <!-- Email Field -->
                    <div class="form-group-custom">
                        <label for="user_email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address <span class="required-asterisk">*</span>
                        </label>
                        <input type="email" name="email" id="user_email" class="form-input" 
                               placeholder="you@example.com" required
                               value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>">
                        <div class="form-text">We'll never share your email with anyone else.</div>
                    </div>
                    
                    <!-- First Impression -->
                    <div class="form-group-custom">
                        <label for="impression" class="form-label">
                            <i class="fas fa-brain"></i> First Impression <span class="required-asterisk">*</span>
                        </label>
                        <textarea name="impression" id="impression" class="form-input" 
                                  placeholder="What was your first impression when you entered our website?" 
                                  rows="4" required maxlength="500"></textarea>
                        <div class="character-count" id="impression-count">0/500 characters</div>
                    </div>
                    
                    <!-- How did you hear about us -->
                    <div class="form-group-custom">
                        <label for="source" class="form-label">
                            <i class="fas fa-bullhorn"></i> How did you hear about us? <span class="required-asterisk">*</span>
                        </label>
                        <select name="source" id="source" class="form-input select-custom" required>
                            <option value="" disabled selected>Select an option</option>
                            <option value="search_engine">Search Engine (Google, Bing, etc.)</option>
                            <option value="social_media">Social Media (Facebook, Instagram, Twitter)</option>
                            <option value="friend_family">Friend or Family Recommendation</option>
                            <option value="advertisement">Online Advertisement</option>
                            <option value="email_marketing">Email Newsletter</option>
                            <option value="blog_article">Blog or Article</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <!-- Missing Features -->
                    <div class="form-group-custom">
                        <label for="missing_features" class="form-label">
                            <i class="fas fa-lightbulb"></i> Suggestions for Improvement
                        </label>
                        <textarea name="missing_features" id="missing_features" class="form-input" 
                                  placeholder="Is there anything missing or could be improved on our website?" 
                                  rows="4" maxlength="1000"></textarea>
                        <div class="character-count" id="features-count">0/1000 characters</div>
                    </div>
                    
                    <!-- Additional Feedback -->
                    <div class="form-group-custom">
                        <label for="additional_feedback" class="form-label">
                            <i class="fas fa-comment-dots"></i> Additional Comments
                        </label>
                        <textarea name="additional_feedback" id="additional_feedback" class="form-input" 
                                  placeholder="Any other comments, suggestions, or concerns?" 
                                  rows="4" maxlength="1000"></textarea>
                        <div class="character-count" id="additional-count">0/1000 characters</div>
                    </div>
                    
                    <!-- Rating System -->
                    <div class="rating-container">
                        <div class="rating-title">
                            <i class="fas fa-star me-2"></i> Overall Rating <span class="required-asterisk">*</span>
                        </div>
                        
                        <div class="rating-stars">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5" title="Excellent"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4" title="Good"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3" title="Average"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2" title="Poor"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1" title="Terrible"><i class="fas fa-star"></i></label>
                        </div>
                        
                        <div class="rating-labels">
                            <span class="rating-label">Terrible</span>
                            <span class="rating-label">Poor</span>
                            <span class="rating-label">Average</span>
                            <span class="rating-label">Good</span>
                            <span class="rating-label">Excellent</span>
                        </div>
                        
                        <div class="rating-feedback" id="rating-feedback">
                            Select a rating
                        </div>
                    </div>
                    
                    <!-- Privacy Note -->
                    <div class="privacy-note">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Privacy Assurance:</strong> Your feedback is anonymous and will only be used to improve our services. We respect your privacy and comply with GDPR regulations.
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" name="feed_but" class="submit-btn mt-4" id="submitBtn">
                        <span class="spinner-border spinner-border-sm" id="spinner" role="status" aria-hidden="true"></span>
                        <span id="btn-text">
                            <i class="fas fa-paper-plane"></i> Submit Feedback
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Character count for textareas
        function updateCharacterCount(textareaId, countId, maxLength) {
            const textarea = document.getElementById(textareaId);
            const countElement = document.getElementById(countId);
            
            textarea.addEventListener('input', function() {
                const length = this.value.length;
                countElement.textContent = `${length}/${maxLength} characters`;
                
                // Update color based on percentage used
                const percentage = (length / maxLength) * 100;
                countElement.className = 'character-count';
                
                if (percentage > 80) {
                    countElement.classList.add('warning');
                }
                if (percentage > 95) {
                    countElement.classList.add('danger');
                }
            });
        }
        
        // Initialize character counters
        updateCharacterCount('impression', 'impression-count', 500);
        updateCharacterCount('missing_features', 'features-count', 1000);
        updateCharacterCount('additional_feedback', 'additional-count', 1000);
        
        // Star rating interaction
        const stars = document.querySelectorAll('.rating-stars input');
        const ratingFeedback = document.getElementById('rating-feedback');
        
        const ratingLabels = {
            1: "Terrible - We're sorry to hear that. We'll work hard to improve.",
            2: "Poor - Thank you for the honest feedback. We'll do better.",
            3: "Average - We appreciate your feedback and will work on improvements.",
            4: "Good - Glad to hear you had a positive experience!",
            5: "Excellent - Thank you for the perfect rating! We're thrilled!"
        };
        
        stars.forEach(star => {
            star.addEventListener('change', function() {
                const value = this.value;
                ratingFeedback.textContent = ratingLabels[value];
                ratingFeedback.style.color = '#ffc107';
            });
        });
        
        // Form validation and submission
        $('#feedbackForm').on('submit', function(e) {
            const submitBtn = $('#submitBtn');
            const btnText = $('#btn-text');
            const spinner = $('#spinner');
            
            // Basic validation
            const email = $('#user_email').val();
            const rating = $('input[name="rating"]:checked').val();
            
            if (!email || !rating) {
                e.preventDefault();
                alert('Please fill in all required fields (email and rating).');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
            
            // Rate limiting check (client-side)
            const lastSubmitTime = <?php echo $_SESSION['last_feedback_time']; ?>;
            const currentTime = Math.floor(Date.now() / 1000);
            const timeSinceLast = currentTime - lastSubmitTime;
            
            if (timeSinceLast < 300) { // 5 minutes
                e.preventDefault();
                const waitTime = 300 - timeSinceLast;
                const waitMinutes = Math.ceil(waitTime / 60);
                alert(`Please wait ${waitMinutes} minute(s) before submitting another feedback.`);
                return;
            }
            
            // Show loading state
            submitBtn.prop('disabled', true);
            btnText.html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
            spinner.show();
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
        
        // Add animation to form elements
        $('.form-group-custom').each(function(index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
            $(this).addClass('animate__animated animate__fadeIn');
        });
        
        // Add tooltips to rating stars
        $('.rating-stars label').tooltip({
            trigger: 'hover',
            placement: 'top'
        });
    });
    
    // Handle browser back/forward cache
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>
</html>

<?php subview('footer.php'); ?>
