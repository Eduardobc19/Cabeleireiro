const btn = document.getElementById("switch-btn");
const text = document.getElementById("switch-text");

const loginForm = document.getElementById("login-form");
const registerForm = document.getElementById("register-form");

const container = document.getElementById("main-container");

btn.onclick = () => {

    loginForm.classList.toggle("active-panel");
    loginForm.classList.toggle("hidden-panel");

    registerForm.classList.toggle("active-panel");
    registerForm.classList.toggle("hidden-panel");

    loginForm.classList.toggle("mobile-hidden");
    registerForm.classList.toggle("mobile-hidden");

    container.classList.toggle("register-mode");

    if (registerForm.classList.contains("active-panel")) {
        text.innerText = "Já tens conta?";
        btn.innerText = "Login";
    } else {
        text.innerText = "Ainda não tens conta?";
        btn.innerText = "Registar";
    }
};
