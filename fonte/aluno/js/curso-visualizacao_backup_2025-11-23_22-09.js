
let itemAtual = null;
let tempoInicio = null;

// Listener global - garante que o botão "Enviar" funcione mesmo se o quiz já estiver carregado
document.addEventListener('click', function (e) {
  const btn = e.target.closest('#enviar-quiz');
  if (btn) {
    e.preventDefault();
    if (typeof enviarRespostasQuiz === 'function') {
      enviarRespostasQuiz();
    } else {
      console.warn('Função enviarRespostasQuiz não encontrada.');
    }
  }
});


function mostrarVisualizacao() {
  console.log("mostrarVisualizacao foi chamada");

  const contentDisplay = document.getElementById('contentDisplay');
  const backButtonArea = document.getElementById('backButtonArea');
  const suggestionsScroll = document.getElementById('suggestionsScroll');

  contentDisplay.innerHTML = '<p style="text-align:center; color:#666;">Área para apresentação da visualização</p>';
  backButtonArea.style.display = 'none';
  suggestionsScroll.innerHTML = '';
}


function voltarVisualizacao() {
   console.log("mostrarVisualizacao foi chamada");

  const display = document.getElementById('contentDisplay');
  const suggestions = document.getElementById('suggestionsScroll');
  const extras = document.getElementById('extrasArea');
  const backButton = document.getElementById('backButtonArea');

  // Ocultar botão de voltar e extras, mostrar sugestões
  backButton.style.display = 'none';
  extras.style.display = 'none';
  suggestions.style.display = 'block';

  // Restaurar conteúdo padrão
  display.innerHTML = `<div class="placeholder-text">Visualização do progresso do aluno aparecerá aqui</div>`;

  // Calcular tempo de uso e registrar
  if (tempoInicio && itemAtual) {
    const tempoFim = new Date();
    const segundos = Math.floor((tempoFim - tempoInicio) / 1000);
    console.log(`Tempo de uso em "${itemAtual}": ${segundos} segundos`);
    registrarInteracao(itemAtual, segundos, false);
  }

  itemAtual = null;
  tempoInicio = null;
}



function registrarInteracao(itemId, tempoSegundos, concluido = false) {
  fetch('../api/interacao.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      item_id: itemId,
      tempo: tempoSegundos,
      concluido: concluido
    })
  })
  .then(response => response.json())
  .then(data => {
    console.log('Interação registrada:', data);
  })
  .catch(error => {
    console.error('Erro ao registrar interação:', error);
  });
}


function rolarAulas(direcao) {
  const container = document.getElementById('aulaTabs');
  const largura = container.offsetWidth;

  switch (direcao) {
    case 'inicio':
      container.scrollLeft = 0;
      break;
    case 'fim':
      container.scrollLeft = container.scrollWidth;
      break;
    case 'esquerda':
      container.scrollLeft -= largura / 2;
      break;
    case 'direita':
      container.scrollLeft += largura / 2;
      break;
  }
}

/* levada para o arquivo curso-adaptativo.js
function marcarConcluido(itemId, el) {
  fetch('marcar_concluido.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'item_id=' + encodeURIComponent(itemId)
  })
  .then(r => r.text())
  .then(txt => {
    const status = txt.trim();
    if (status === 'concluido') {
      el.classList.add('completed');  // pinta o ícone ✓ de verde
      el.textContent = '✓';
    } else if (status === 'desfeito') {
      el.classList.remove('completed');
      el.textContent = 'o';
    } else {
      alert('Erro ao atualizar: ' + txt);
    }
  })
  .catch(err => {
    console.error(err);
    alert('Erro ao marcar/desmarcar item.');
  });
}
*/

/* old
function marcarConcluido(itemId, el) {
  fetch('marcar_concluido.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'item_id=' + encodeURIComponent(itemId)
  })
  .then(response => response.text())
  .then(data => {
    // el = <span class="check-icon ...">
    const container = el.closest('.content-item'); // ← pega o container do menu

    if (data.trim() === 'concluido') {
      el.classList.add('completed');
      el.textContent = '✓';
      if (container) container.classList.add('completed');   // ← pinta de verde agora
    } else if (data.trim() === 'desfeito') {
      el.classList.remove('completed');
      el.textContent = 'o';
      if (container) container.classList.remove('completed'); // ← remove o verde
    } else {
      alert('Erro ao atualizar: ' + data);
    }
  })
  .catch(error => {
    console.error('Erro:', error);
    alert('Erro ao marcar/desmarcar item.');
  });
}

*/

//usado no checklist - tooltep flutuante e etc
function toggleDica(event, id) {
  event.stopPropagation();

  const tooltip = document.getElementById(id);
  const allTooltips = document.querySelectorAll('.dica-tooltip');

  // Fecha outros tooltips
  allTooltips.forEach(t => {
    if (t !== tooltip) {
      t.classList.remove('show');
      setTimeout(() => t.style.display = 'none', 300);
    }
  });

  const isVisible = tooltip.classList.contains('show');

  if (isVisible) {
    tooltip.classList.remove('show');
    setTimeout(() => tooltip.style.display = 'none', 300);
  } else {
    tooltip.style.display = 'block';

    // Posicionamento inteligente
    const iconRect = event.target.getBoundingClientRect();
    const tooltipWidth = tooltip.offsetWidth;
    const padding = 10;
    const spaceLeft = iconRect.left;
    const spaceRight = window.innerWidth - iconRect.right;

    tooltip.style.top = iconRect.bottom + window.scrollY + 'px';

    if (spaceLeft >= tooltipWidth + padding) {
      tooltip.style.left = (iconRect.right - tooltipWidth) + window.scrollX + 'px';
    } else {
      tooltip.style.left = iconRect.left + window.scrollX + 'px';
    }

    // Mostra com fade
    setTimeout(() => tooltip.classList.add('show'), 10);
  }
}

// Fecha ao clicar fora
document.addEventListener('click', () => {
  document.querySelectorAll('.dica-tooltip').forEach(t => {
    t.classList.remove('show');
    setTimeout(() => t.style.display = 'none', 300);
  });
});


//cuida  do conteudo da checklist
function monitorarChecklist() {
  document.querySelectorAll('.checklist-item input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function () {
      const materialId = this.dataset.materialId;
      const checklistItemId = this.dataset.itemId;
      const checked = this.checked;
//      alert("Material==>".materialId);

console.log(materialId);

      fetch('salvar_checklist.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `material_id=${materialId}&checklist_item_id=${checklistItemId}&checked=${checked}`
      });

      atualizarBarraDeProgresso();
      // verificarConclusaoChecklist(); // pode ser ativado se necessário
    });
  });

  // Atualiza ao carregar a tela
  atualizarBarraDeProgresso();
}


/*  fora de uso
function verificarConclusaoChecklist() {
  const todos = document.querySelectorAll('.checklist-item input[type="checkbox"]');
  const marcados = Array.from(todos).filter(cb => cb.checked);

  if (marcados.length === todos.length && todos.length > 0) {
    alert('🎉 Parabéns! Você concluiu todas as etapas!');
  }
}
*/

function atualizarBarraDeProgresso() {
  const todos = document.querySelectorAll('.checklist-item input[type="checkbox"]');
  const marcados = Array.from(todos).filter(cb => cb.checked);
  const progresso = (marcados.length / todos.length) * 100;

  const barra = document.getElementById('progressoInterno');
  if (barra) {
    barra.style.width = progresso + '%';
  }
}

/* Botão da quiz */


window.enviarRespostasQuiz = function () {
  const form = document.getElementById("quiz-form");
  const btn = document.getElementById("enviar-quiz");

  if (!form || !btn) {
    console.warn("Formulário ou botão não encontrado.");
    return;
  }

  // Verifica se todas as perguntas foram respondidas
  const perguntas = form.querySelectorAll("[name^='pergunta_']");
  const perguntasRespondidas = new Set();

  perguntas.forEach(input => {
    if (input.checked) {
      perguntasRespondidas.add(input.name);
    }
  });

  const totalPerguntas = new Set(Array.from(perguntas).map(input => input.name));

  if (perguntasRespondidas.size < totalPerguntas.size) {
    return;
  }

  const formData = new FormData(form);

  fetch("salvar_respostas.php", {
    method: "POST",
    body: formData
  })
    .then(response => response.text())
    .then(data => {
      // Limpa o conteúdo da tela
      document.body.innerHTML = "";

      // Cria a mensagem visual
      const msg = document.createElement("div");
      msg.innerHTML = `
        <div style="
          margin: 100px auto;
          padding: 20px 30px;
          background: #d4edda;
          color: #155724;
          border: 1px solid #c3e6cb;
          border-radius: 8px;
          font-size: 18px;
          text-align: center;
          width: fit-content;
          box-shadow: 0 0 6px rgba(0,0,0,0.1);
        ">
          ✅ Respostas enviadas com sucesso!<br>
          <small>Você será redirecionado automaticamente...</small>
        </div>
      `;
      document.body.appendChild(msg);

      // Redireciona para /curso/curso.php após 2 segundos
      setTimeout(() => {
        window.location.href = "curso.php";
      }, 2000);
    })
    .catch(error => {
      console.error("Erro ao enviar:", error);
    });
};
