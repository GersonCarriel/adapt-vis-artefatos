document.addEventListener("DOMContentLoaded", function () {
  const btn = document.getElementById("enviar-quiz");
  const form = document.getElementById("quiz-form");

  if (btn && form) {
    btn.addEventListener("click", function () {
      const formData = new FormData(form);

      fetch("salvar_respostas.php", {
        method: "POST",
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        // Removido alert(data);
        // Se quiser registrar no console, pode manter:
        console.log("Respostas enviadas:", data);
      })
      .catch(error => {
        console.error("Erro ao enviar:", error);
        // Removido alert de erro
      });
    });
  }
});
