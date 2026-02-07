<?php
// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<form method="POST" action="includes/register.inc.php" id="registerForm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    
    <div class="form-group">
        <label for="username">Username *</label>
        <input type="text" id="username" name="username" 
               pattern="[a-zA-Z0-9_]{3,30}"
               title="3-30 characters, letters, numbers, underscores only"
               value="<?php echo htmlspecialchars($_SESSION['form_data']['username'] ?? ''); ?>"
               required>
    </div>
    
    <div class="form-group">
        <label for="email_id">Email *</label>
        <input type="email" id="email_id" name="email_id" 
               value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>"
               required>
    </div>
    
    <div class="form-group">
        <label for="password">Password *</label>
        <input type="password" id="password" name="password" 
               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{12,}$"
               title="At least 12 characters, with uppercase, lowercase, number, and special character"
               required>
        <div class="password-strength">
            <div class="strength-meter"></div>
            <div class="strength-feedback"></div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="password_repeat">Confirm Password *</label>
        <input type="password" id="password_repeat" name="password_repeat" required>
    </div>
    
    <div class="form-group">
        <label for="referral_code">Referral Code (Optional)</label>
        <input type="text" id="referral_code" name="referral_code">
    </div>
    
    <div class="form-check">
        <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
        <label for="terms" class="form-check-label">
            I agree to the <a href="/terms.php" target="_blank">Terms and Conditions</a> and <a href="/privacy.php" target="_blank">Privacy Policy</a> *
        </label>
    </div>
    
    <div class="form-check">
        <input type="checkbox" id="newsletter" name="newsletter" class="form-check-input"
               <?php echo isset($_SESSION['form_data']['newsletter']) && $_SESSION['form_data']['newsletter'] ? 'checked' : ''; ?>>
        <label for="newsletter" class="form-check-label">
            Subscribe to our newsletter
        </label>
    </div>
    
    <!-- Add reCAPTCHA v3 -->
    <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
    
    <button type="submit" name="signup_submit" class="btn-register">
        Create Account
    </button>
</form>

<!-- Password strength checker -->
<script>
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strength = checkPasswordStrength(password);
    updateStrengthMeter(strength);
});

function checkPasswordStrength(password) {
    let score = 0;
    
    // Length
    if (password.length >= 12) score += 2;
    else if (password.length >= 8) score += 1;
    
    // Character variety
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;
    
    // No repeated characters
    if (!/(.)\1{2,}/.test(password)) score += 1;
    
    // No sequential patterns
    if (!/(abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz)/i.test(password)) {
        score += 1;
    }
    if (!/(012|123|234|345|456|567|678|789|890)/.test(password)) {
        score += 1;
    }
    
    return Math.min(score, 10); // Max score 10
}

function updateStrengthMeter(score) {
    const meter = document.querySelector('.strength-meter');
    const feedback = document.querySelector('.strength-feedback');
    
    let strength = 'weak';
    let color = '#dc3545';
    let message = 'Weak password';
    
    if (score >= 7) {
        strength = 'strong';
        color = '#28a745';
        message = 'Strong password';
    } else if (score >= 4) {
        strength = 'medium';
        color = '#ffc107';
        message = 'Medium strength';
    }
    
    meter.style.width = (score * 10) + '%';
    meter.style.backgroundColor = color;
    feedback.textContent = message;
    feedback.style.color = color;
}
</script>

<!-- reCAPTCHA v3 -->
<script src="https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY"></script>
<script>
grecaptcha.ready(function() {
    grecaptcha.execute('YOUR_SITE_KEY', {action: 'register'}).then(function(token) {
        document.getElementById('g-recaptcha-response').value = token;
    });
});
</script>
