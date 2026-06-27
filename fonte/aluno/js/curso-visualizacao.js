// ====== DADOS HARDCODED DOS CONHECIMENTOS (Aula 1) ======
const NODES_AULA1 = [
  // Linha 1 – Conceitos base
  {
    code: "A1K01",
    title: "Lógica",
    desc: "Pensar de forma organizada para resolver problemas.",
    status: "OK",
    x: 140, y: 80,
    grupo: "A1G01",
    grupoLabel: "Conceitos básicos",
    ordem: 1
  },
  {
    code: "A1K02",
    title: "Algoritmo",
    desc: "Sequência de passos organizados para resolver um problema.",
    status: "OK",
    x: 320, y: 80,
    grupo: "A1G01",
    grupoLabel: "Conceitos básicos",
    ordem: 2
  },
  {
    code: "A1K03",
    title: "Lóg/Alg/Prog",
    desc: "Entender a relação entre lógica, algoritmo e programação.",
    status: "ATENCAO",
    x: 500, y: 80,
    grupo: "A1G01",
    grupoLabel: "Conceitos básicos",
    ordem: 3
  },
  {
    code: "A1K04",
    title: "A → B",
    desc: "Pensar em sair do ponto A (situação atual) e chegar ao ponto B (resultado desejado).",
    status: "NEUTRO",
    x: 680, y: 80,
    grupo: "A1G01",
    grupoLabel: "Conceitos básicos",
    ordem: 4
  },

  // Linha 2 – Modelo Entrada–Proc–Mem–Saída
  {
    code: "A1K07",
    title: "Entrada",
    desc: "Dados que entram no programa (por exemplo, algo digitado pelo usuário).",
    status: "OK",
    x: 220, y: 210,
    grupo: "A1G02",
    grupoLabel: "Modelo E–P–M–S",
    ordem: 7
  },
  {
    code: "A1K08",
    title: "Processamento",
    desc: "Cálculos e decisões que o programa faz com os dados de entrada.",
    status: "NEUTRO",
    x: 400, y: 210,
    grupo: "A1G02",
    grupoLabel: "Modelo E–P–M–S",
    ordem: 8
  },
  {
    code: "A1K09",
    title: "Memória",
    desc: "Onde o programa guarda temporariamente valores enquanto está rodando.",
    status: "ATENCAO",
    x: 580, y: 210,
    grupo: "A1G02",
    grupoLabel: "Modelo E–P–M–S",
    ordem: 9
  },
  {
    code: "A1K10",
    title: "Saída",
    desc: "Resultados que o programa mostra para o usuário (como textos na tela).",
    status: "NEUTRO",
    x: 760, y: 210,
    grupo: "A1G02",
    grupoLabel: "Modelo E–P–M–S",
    ordem: 10
  },

  // Linha 3 – Python concretizando o modelo
  {
    code: "A1K11",
    title: "print()",
    desc: "Comando que mostra informações na tela (saída).",
    status: "OK",
    x: 260, y: 330,
    grupo: "A1G03",
    grupoLabel: "Python na prática",
    ordem: 11
  },
  {
    code: "A1K12",
    title: "Variáveis",
    desc: "Nomes usados para guardar valores na memória (como caixinhas que armazenam dados).",
    status: "ATENCAO",
    x: 440, y: 330,
    grupo: "A1G03",
    grupoLabel: "Python na prática",
    ordem: 12
  },
  {
    code: "A1K13",
    title: "c = a + b",
    desc: "Exemplo de processamento com variáveis: o computador calcula e guarda o resultado.",
    status: "NEUTRO",
    x: 620, y: 330,
    grupo: "A1G03",
    grupoLabel: "Python na prática",
    ordem: 13
  },
  {
    code: "A1K14",
    title: "input()",
    desc: "Comando que pede um dado ao usuário (entrada).",
    status: "NEUTRO",
    x: 800, y: 330,
    grupo: "A1G03",
    grupoLabel: "Python na prática",
    ordem: 14
  }
];

// Arestas do grafo (hardcoded)
const EDGES_AULA1 = [
  { from: "A1K01", to: "A1K02" },
  { from: "A1K02", to: "A1K03" },
  { from: "A1K01", to: "A1K04" },
  { from: "A1K02", to: "A1K07" },
  { from: "A1K07", to: "A1K08" },
  { from: "A1K08", to: "A1K09" },
  { from: "A1K09", to: "A1K10" },
  { from: "A1K10", to: "A1K11" },
  { from: "A1K09", to: "A1K12" },
  { from: "A1K12", to: "A1K13" },
  { from: "A1K07", to: "A1K14" }
];

// Cor por status
function statusColor(status) {
  if (status === "OK") return "#2ecc71";       // verde
  if (status === "ATENCAO") return "#e67e22";  // laranja
  return "#95a5a6";                            // cinza
}

// Estado atual do modo de visualização
let modoVisualizacaoAtual = "grafo";
let visualizacaoInicializada = false;


// Linhas acima para visualização hardcoded

//Linhas para visualização pegar dados de  Json
// Estado vindo do backend
let statusConhecimentosCarregado = false;
let statusConhecimentosPorCodigo = {};


function carregarStatusConhecimentos() {
  // Se já carregou uma vez, não precisa buscar de novo
  if (statusConhecimentosCarregado) {
    return Promise.resolve(statusConhecimentosPorCodigo);
  }

  return fetch('visualizacao_a1.php')
    .then(res => {
      if (!res.ok) {
        throw new Error('Erro ao buscar visualização: ' + res.status);
      }
      return res.json();
    })
    .then(dados => {
      statusConhecimentosPorCodigo = {};
      dados.forEach(item => {
        if (item.codigo && item.status) {
          statusConhecimentosPorCodigo[item.codigo] = item.status;
        }
      });

      // Aplicar no array de nós (NODES_AULA1)
      NODES_AULA1.forEach(node => {
        if (statusConhecimentosPorCodigo[node.code]) {
          node.status = statusConhecimentosPorCodigo[node.code];
        }
      });

      statusConhecimentosCarregado = true;
      return statusConhecimentosPorCodigo;
    })
    .catch(err => {
      console.error('Falha ao carregar status dos conhecimentos:', err);
      // Se der erro, seguimos com os status hardcoded
      return {};
    });
}




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

/*
function mostrarVisualizacao() {
  console.log("mostrarVisualizacao foi chamada");

  const contentDisplay = document.getElementById('contentDisplay');
  const backButtonArea = document.getElementById('backButtonArea');
  const suggestionsScroll = document.getElementById('suggestionsScroll');

  contentDisplay.innerHTML = '<p style="text-align:center; color:#666;">Área para apresentação da visualização</p>';
  backButtonArea.style.display = 'none';
  suggestionsScroll.innerHTML = '';
}


function mostrarVisualizacao() {
  const backButtonArea = document.getElementById("backButtonArea");
  if (backButtonArea) {
    // Estamos na visualização: não faz sentido mostrar o botão "← Visualização"
    backButtonArea.style.display = "none";
  }

  // Aqui NÃO vamos mais mexer no contentDisplay inteiro.
  // Vamos apenas desenhar (ou redesenhar) o grafo estático
  // dentro das áreas já criadas no curso.php.
  renderizarGrafoEstatico();
}
*/

function mostrarVisualizacao() {
  const backButtonArea = document.getElementById("backButtonArea");
  if (backButtonArea) {
    backButtonArea.style.display = "none";
  }

  inicializarVisualizacaoModos();
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


function renderizarGrafoEstatico() {
  const graphArea = document.getElementById("graph-area");
  const infoBox = document.getElementById("graph-info");
  const suggestionsBox = document.getElementById("graph-suggestions");

  if (!graphArea || !infoBox || !suggestionsBox) {
    console.warn("Áreas da visualização não encontradas no curso.php.");
    return;
  }

  // Limpa área e reseta textos
  graphArea.innerHTML = "";
  infoBox.innerHTML = "Passe o mouse sobre um conhecimento para ver uma explicação.";
  suggestionsBox.innerHTML = "Clique em um conhecimento para ver sugestões de estudo relacionadas.";

  const SVG_NS = "http://www.w3.org/2000/svg";
  const svg = document.createElementNS(SVG_NS, "svg");
  svg.setAttribute("viewBox", "0 0 900 400");
  svg.style.maxWidth = "100%";
  svg.style.maxHeight = "100%";

  // Definição da seta
  const defs = document.createElementNS(SVG_NS, "defs");
  const marker = document.createElementNS(SVG_NS, "marker");
  marker.setAttribute("id", "arrowhead");
  marker.setAttribute("markerWidth", "6");
  marker.setAttribute("markerHeight", "6");
  marker.setAttribute("refX", "5");
  marker.setAttribute("refY", "3");
  marker.setAttribute("orient", "auto");

  const markerPath = document.createElementNS(SVG_NS, "path");
  markerPath.setAttribute("d", "M0,0 L6,3 L0,6 Z");
  markerPath.setAttribute("fill", "#888");

  marker.appendChild(markerPath);
  defs.appendChild(marker);
  svg.appendChild(defs);

  const getNode = code => NODES_AULA1.find(n => n.code === code);

  // Arestas
  EDGES_AULA1.forEach(edge => {
    const a = getNode(edge.from);
    const b = getNode(edge.to);
    if (!a || !b) return;

    const line = document.createElementNS(SVG_NS, "line");
    line.setAttribute("x1", a.x);
    line.setAttribute("y1", a.y);
    line.setAttribute("x2", b.x);
    line.setAttribute("y2", b.y);
    line.setAttribute("stroke", "#888");
    line.setAttribute("stroke-width", "2");
    line.setAttribute("marker-end", "url(#arrowhead)");
    svg.appendChild(line);
  });

  // Nós
  NODES_AULA1.forEach(node => {
    const g = document.createElementNS(SVG_NS, "g");
    g.setAttribute("transform", `translate(${node.x}, ${node.y})`);
    g.style.cursor = "pointer";

    const circle = document.createElementNS(SVG_NS, "circle");
    circle.setAttribute("r", 28);
    circle.setAttribute("fill", statusColor(node.status));
    circle.setAttribute("stroke", "#333");
    circle.setAttribute("stroke-width", "1.5");

    const text = document.createElementNS(SVG_NS, "text");
    text.setAttribute("text-anchor", "middle");
    text.setAttribute("dy", "5");
    text.setAttribute("font-size", "11");
    text.setAttribute("fill", "#fff");
    text.textContent = node.title;

    g.addEventListener("mouseenter", () => {
      infoBox.innerHTML = `<b>${node.code} — ${node.title}</b><br>${node.desc}`;
    });

    g.addEventListener("mouseleave", () => {
      infoBox.innerHTML = "Passe o mouse sobre um conhecimento para ver uma explicação.";
    });


g.addEventListener("click", () => {
  if (node.status !== "ATENCAO") {
    // Nada especial para verde ou cinza
    suggestionsBox.innerHTML = `
      Este conhecimento está <b>OK</b> ou ainda não foi alvo de revisão específica.
      No momento, não há sugestões extras para ele.
    `;
    return;
  }

  suggestionsBox.innerHTML = `
    <b>Sugestões relacionadas a ${node.title}:</b><br>
    • Rever explicação da Aula 1 sobre <b>${node.title}</b><br>
    • Assistir vídeo complementar<br>
    • Refazer exercícios do notebook<br>
    (Sugestões reais serão carregadas do banco mais tarde)
  `;
});



    g.appendChild(circle);
    g.appendChild(text);
    svg.appendChild(g);
  });

  graphArea.appendChild(svg);
}

//Fim Renderiza grafo

function renderizarTrilhaEstatico() {
  const graphArea = document.getElementById("graph-area");
  const infoBox = document.getElementById("graph-info");
  const suggestionsBox = document.getElementById("graph-suggestions");

  if (!graphArea || !infoBox || !suggestionsBox) return;

  graphArea.innerHTML = "";
  infoBox.innerHTML = "Passe o mouse sobre um conhecimento na trilha para ver detalhes.";
  suggestionsBox.innerHTML = "Clique em um conhecimento na trilha para ver sugestões de estudo.";

  // Container da trilha
  const trilha = document.createElement("div");
  trilha.style.display = "flex";
  trilha.style.flexWrap = "wrap";
  trilha.style.alignItems = "center";
  trilha.style.gap = "8px";
  trilha.style.justifyContent = "center";

  // Ordena por ordem
  const ordenados = [...NODES_AULA1].sort((a, b) => a.ordem - b.ordem);

  ordenados.forEach((node, index) => {
    const card = document.createElement("div");
    card.style.minWidth = "110px";
    card.style.padding = "6px 8px";
    card.style.borderRadius = "8px";
    card.style.border = "1px solid #ddd";
    card.style.display = "flex";
    card.style.flexDirection = "column";
    card.style.alignItems = "center";
    card.style.background = "#fdfdfd";
    card.style.cursor = "pointer";

    const bolinha = document.createElement("div");
    bolinha.style.width = "16px";
    bolinha.style.height = "16px";
    bolinha.style.borderRadius = "50%";
    bolinha.style.background = statusColor(node.status);
    bolinha.style.marginBottom = "4px";

    const label = document.createElement("div");
    label.textContent = node.title;
    label.style.fontSize = "11px";
    label.style.textAlign = "center";

    card.appendChild(bolinha);
    card.appendChild(label);

    card.addEventListener("mouseenter", () => {
      infoBox.innerHTML = `<b>${node.code} — ${node.title}</b><br>${node.desc}`;
    });

    card.addEventListener("mouseleave", () => {
      infoBox.innerHTML = "Passe o mouse sobre um conhecimento na trilha para ver detalhes.";
    });

card.addEventListener("click", () => {
  if (node.status !== "ATENCAO") {
    suggestionsBox.innerHTML = `
      Este conhecimento está <b>OK</b> ou ainda não foi alvo de revisão específica.
      No momento, não há sugestões extras para ele.
    `;
    return;
  }

  suggestionsBox.innerHTML = `
    <b>Sugestões relacionadas a ${node.title}:</b><br>
    • Rever explicação da Aula 1 sobre <b>${node.title}</b><br>
    • Assistir vídeo complementar<br>
    • Refazer exercícios do notebook<br>
    (Sugestões reais serão carregadas do banco mais tarde)
  `;
});


    trilha.appendChild(card);

    // seta entre os cards (exceto depois do último)
    if (index < ordenados.length - 1) {
      const seta = document.createElement("span");
      seta.textContent = "→";
      seta.style.fontSize = "16px";
      seta.style.color = "#888";
      trilha.appendChild(seta);
    }
  });

  graphArea.appendChild(trilha);
}

//Fim renderiza  Trilha

function renderizarDashboardEstatico() {
  const graphArea = document.getElementById("graph-area");
  const infoBox = document.getElementById("graph-info");
  const suggestionsBox = document.getElementById("graph-suggestions");

  if (!graphArea || !infoBox || !suggestionsBox) return;

  graphArea.innerHTML = "";
  infoBox.innerHTML = "Passe o mouse sobre um grupo ou segmento para ver detalhes.";
  suggestionsBox.innerHTML = "Clique em um segmento laranja para ver sugestões de estudo daquele conhecimento.";

  // Agrupa por grupoLabel
  const gruposMap = {};
  NODES_AULA1.forEach(node => {
    const key = node.grupoLabel || "Outros";
    if (!gruposMap[key]) {
      gruposMap[key] = [];
    }
    gruposMap[key].push(node);
  });

  const container = document.createElement("div");
  container.style.display = "flex";
  container.style.flexDirection = "column";
  container.style.gap = "10px";
  container.style.width = "100%";

  Object.keys(gruposMap).forEach(grupoLabel => {
    const nodesGrupo = gruposMap[grupoLabel];

    const bloco = document.createElement("div");
    bloco.style.fontSize = "13px";

    const titulo = document.createElement("div");
    titulo.textContent = grupoLabel;
    titulo.style.fontWeight = "bold";
    titulo.style.marginBottom = "4px";
    bloco.appendChild(titulo);

    const barra = document.createElement("div");
    barra.style.display = "flex";
    barra.style.gap = "4px";

    nodesGrupo.forEach(node => {
      const seg = document.createElement("div");
      seg.style.flex = "1";
      seg.style.height = "18px";
      seg.style.borderRadius = "4px";
      seg.style.background = statusColor(node.status);
      seg.style.cursor = "pointer";
      seg.title = `${node.code} – ${node.title}`;

      seg.addEventListener("mouseenter", () => {
        infoBox.innerHTML = `<b>${grupoLabel}</b><br>${node.code} — ${node.title}<br>${node.desc}`;
      });

      seg.addEventListener("mouseleave", () => {
        infoBox.innerHTML = "Passe o mouse sobre um grupo ou segmento para ver detalhes.";
      });

seg.addEventListener("click", () => {
  if (node.status !== "ATENCAO") {
    suggestionsBox.innerHTML = `
      Este conhecimento está <b>OK</b> ou ainda não foi alvo de revisão específica
      dentro do grupo <b>${grupoLabel}</b>. Não há sugestões extras para ele agora.
    `;
    return;
  }

  suggestionsBox.innerHTML = `
    <b>Sugestões relacionadas a ${node.title}:</b><br>
    • Rever explicação da Aula 1 sobre <b>${node.title}</b><br>
    • Refazer questões do quiz relacionadas<br>
    • Revisar materiais do grupo <b>${grupoLabel}</b><br>
    (Sugestões reais serão carregadas do banco mais tarde)
  `;
});



      barra.appendChild(seg);
    });

    bloco.appendChild(barra);
    container.appendChild(bloco);
  });

  graphArea.appendChild(container);
}



// Fim renderiza visualização dashboard
//Listener da visualização em Curso.php
document.addEventListener("DOMContentLoaded", () => {
  // Ao abrir o curso.php, já mostrar a visualização
  mostrarVisualizacao();
});


function setModoVisualizacao(modo) {
  modoVisualizacaoAtual = modo;

  const btnGrafo = document.getElementById("btn-modo-grafo");
  const btnTrilha = document.getElementById("btn-modo-trilha");
  const btnDash  = document.getElementById("btn-modo-dashboard");

  if (btnGrafo && btnTrilha && btnDash) {
    // Estilo simples de "ativo"
    btnGrafo.style.background = (modo === "grafo") ? "#e0f0ff" : "#f5f5f5";
    btnTrilha.style.background = (modo === "trilha") ? "#e0f0ff" : "#f5f5f5";
    btnDash.style.background  = (modo === "dashboard") ? "#e0f0ff" : "#f5f5f5";
  }

  if (modo === "grafo") {
    renderizarGrafoEstatico();
  } else if (modo === "trilha") {
    renderizarTrilhaEstatico();
  } else if (modo === "dashboard") {
    renderizarDashboardEstatico();
  }
}

function inicializarVisualizacaoModos() {
  // Se já foi inicializada antes, só recarrega o modo grafo
  // (ainda assim buscando status do backend, se precisar)
  if (visualizacaoInicializada) {
    carregarStatusConhecimentos().then(() => {
      setModoVisualizacao("grafo");
    });
    return;
  }

  visualizacaoInicializada = true;

  const btnGrafo  = document.getElementById("btn-modo-grafo");
  const btnTrilha = document.getElementById("btn-modo-trilha");
  const btnDash   = document.getElementById("btn-modo-dashboard");

  if (btnGrafo) {
    btnGrafo.addEventListener("click", () => {
      carregarStatusConhecimentos().then(() => {
        setModoVisualizacao("grafo");
      });
    });
  }

  if (btnTrilha) {
    btnTrilha.addEventListener("click", () => {
      carregarStatusConhecimentos().then(() => {
        setModoVisualizacao("trilha");
      });
    });
  }

  if (btnDash) {
    btnDash.addEventListener("click", () => {
      carregarStatusConhecimentos().then(() => {
        setModoVisualizacao("dashboard");
      });
    });
  }

  // Primeira vez: carrega status do backend e desenha o grafo
  carregarStatusConhecimentos().then(() => {
    setModoVisualizacao("grafo");
  });
}


