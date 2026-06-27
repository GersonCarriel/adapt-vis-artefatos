document.addEventListener("DOMContentLoaded", function () {
  // Validação de Aluno
  const alunoForm = document.querySelector("#form-aluno");
  if (alunoForm) {
    alunoForm.addEventListener("submit", function (e) {
      const nome = alunoForm.querySelector("[name='nome']").value.trim();
      const email = alunoForm.querySelector("[name='email']").value.trim();
      const prontuario = alunoForm.querySelector("[name='prontuario']").value.trim();

      if (nome.length < 3) {
        alert("O nome do aluno deve ter pelo menos 3 letras.");
        e.preventDefault();
        return;
      }

      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert("Email inválido.");
        e.preventDefault();
        return;
      }

      if (prontuario.length < 1 || prontuario.length > 10) {
        alert("Prontuário deve ter até 10 caracteres.");
        e.preventDefault();
        return;
      }
    });
  }

  // Validação de Professor
  const professorForm = document.querySelector("#form-professor");
  if (professorForm) {
    professorForm.addEventListener("submit", function (e) {
      const siape = professorForm.querySelector("[name='siape']").value.trim();

      if (!/^\d{7}$/.test(siape)) {
        alert("SIAPE deve conter exatamente 7 dígitos numéricos.");
        e.preventDefault();
        return;
      }
    });
  }

  // Validação de Usuário
  const usuarioForm = document.querySelector("#form-usuario");
  if (usuarioForm) {
    usuarioForm.addEventListener("submit", function (e) {
      const telefone = usuarioForm.querySelector("[name='telefone']").value.trim();
      const ltiSub = usuarioForm.querySelector("[name='lti_sub']").value.trim();
      const ltiIssuer = usuarioForm.querySelector("[name='lti_issuer']").value.trim();

      if (telefone && !/^\(\d{2}\)\s?\d{4,5}-\d{4}$/.test(telefone)) {
        alert("Telefone inválido. Use o formato (xx) xxxxx-xxxx.");
        e.preventDefault();
        return;
      }

      if (!ltiSub) {
        alert("Campo LTI Sub é obrigatório.");
        e.preventDefault();
        return;
      }

      if (!ltiIssuer) {
        alert("Campo LTI Issuer é obrigatório.");
        e.preventDefault();
        return;
      }
    });
  }
});
