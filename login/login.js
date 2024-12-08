document.addEventListener("DOMContentLoaded", function() {
    if (document.getElementById("searchButton")) {
        document.getElementById("searchButton").addEventListener("click", function(event) {
            event.preventDefault();
            const inputField = document.querySelector(".forgot-pass-input input").value.trim();
            if (inputField !== "") {
                codeContainer();
            } else {
                alert("Please enter your email.");
            }
        });
    }

    if (document.getElementById("continueCode")) {
        document.getElementById("continueCode").addEventListener("click", function(event) {
            event.preventDefault();
            const inputField = document.querySelector(".enter-code-container input").value.trim();
            if (inputField !== "") {
                changePassContainer();
            } else {
                alert("Please enter code.");
            }
        });
    }

    if (document.getElementById("continueButton")) {
        document.getElementById("continueButton").addEventListener("click", function(event) {
            event.preventDefault();
            const inputField = document.querySelector(".change-pass-input input").value.trim();
            if (inputField !== "") {
                window.location.href = "login-form.php";
            } else {
                alert("Please enter your new password.");
            }
        });
    }

    if (document.getElementById("cancelSearch")) {
        document.getElementById("cancelSearch").addEventListener("click", function(event) {
            event.preventDefault();
            window.location.href = "login-form.php";
        });
    }

    if (document.getElementById("cancelCode")) {
        document.getElementById("cancelCode").addEventListener("click", function(event) {
            event.preventDefault();
            forgotPassContainer();
        });
    }

    if (document.getElementById("cancelChange")) {
        document.getElementById("cancelChange").addEventListener("click", function(event) {
            event.preventDefault();
            forgotPassContainer();
        });
    }

    function forgotPassContainer() {
        const forgotPassForm = document.querySelector(".forgot-pass-form-container");
        const searchForm = document.querySelector(".search-form-container");
        const changePassForm = document.querySelector(".change-pass-form-container");

        if (forgotPassForm && searchForm && changePassForm) {
            forgotPassForm.style.display = "block";
            searchForm.style.display = "none";
            changePassForm.style.display = "none";
        }
    }

    function codeContainer() {
        const forgotPassForm = document.querySelector(".forgot-pass-form-container");
        const searchForm = document.querySelector(".search-form-container");
        const changePassForm = document.querySelector(".change-pass-form-container");

        if (forgotPassForm && searchForm && changePassForm) {
            forgotPassForm.style.display = "none";
            searchForm.style.display = "block";
            changePassForm.style.display = "none";
        }
    }

    function changePassContainer() {
        const forgotPassForm = document.querySelector(".forgot-pass-form-container");
        const searchForm = document.querySelector(".search-form-container");
        const changePassForm = document.querySelector(".change-pass-form-container");

        if (forgotPassForm && searchForm && changePassForm) {
            forgotPassForm.style.display = "none";
            searchForm.style.display = "none";
            changePassForm.style.display = "block";
        }
    }
});
