// requires Bootstrap 4 / zxcvbn
(function ($) {
    var settings;
    $.fn.zxcvbnProgress = function (options) {
        settings = $.extend({
            ratings: ["Very Weak", "weak", "OK", "Strong", "Very strong"],
            progressClasses: ['bg-danger', 'bg-warning', 'bg-warning', 'bg-success', 'bg-success']
        }, options);
        var $passwordInput = $(settings.passwordInput),
            $progress = this;
        if (!settings.passwordInput) throw new TypeError('Please enter a password');
        $passwordInput.on('keyup', function () {
            updateProgress($passwordInput, $progress);
        });
        $passwordInput.on('change', function () {
            updateProgress($passwordInput, $progress);
        });
        updateProgress($passwordInput, $progress);
    };
    function updateProgress($passwordInput, $progress) {
        var passwordValue = $passwordInput.val();
        if (passwordValue) {
            var result = zxcvbn(passwordValue, settings.userInputs),
                score = result.score,
                scorePercentage = (score + 1) * 20;
			if(result.score == 0) { $("#addUserButton").prop("disabled",true); }
			if(result.score == 1) { $("#addUserButton").prop("disabled",true); }
			if(result.score == 2) { $("#addUserButton").prop("disabled",true); }
			if(result.score == 3) { $("#addUserButton").prop("disabled",true); }
			if(result.score == 4) { $("#addUserButton").prop("disabled",false); }
            $progress.css('width', scorePercentage + '%');
            $progress.removeClass(settings.progressClasses.join(' ')).addClass(settings.progressClasses[score]).text(settings.ratings[score]);
        } else {
            $progress.css('width', 0 + '%');
            $progress.removeClass(settings.progressClasses.join(' ')).text('');			
        }
    }
})(jQuery);
