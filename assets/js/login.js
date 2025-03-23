$(document).ready(function() {
    // Input animation handling
    $('.input-field').each(function() {
        if ($(this).val()) {
            $(this).next('.floating-label').addClass('active');
        }
    });

    // Form submission handling
    $('#loginForm').submit(function(e) {
e.preventDefault();
const form = this;
const btn = $(this).find('.login-btn');
btn.find('.login-text').addClass('hidden');
btn.find('.loading').removeClass('hidden');
btn.prop('disabled', true);

// Simulate API call
setTimeout(() => {
btn.find('.login-text').removeClass('hidden');
btn.find('.loading').addClass('hidden');
btn.prop('disabled', false);

// Now manually submit the form
form.submit(); // This bypasses the jQuery submit event to avoid infinite loop
}, 1500);
});


    // Hover effects
    $('.login-btn').hover(
        () => $(this).css('transform', 'translateY(-1px)'),
        () => $(this).css('transform', 'translateY(0)')
    );
});
