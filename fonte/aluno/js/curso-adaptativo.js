// Variável global para rastrear o tempo de início da interação
//let tempoInicio = null;

// 🧠 Função para abrir conteúdo adaptado
function abrirConteudo(itemId, materialId = null) {
  console.log(" item:", itemId);
  console.log(" materiaId:", materialId);

  let url = 'conteudo.php?item_id=' + itemId;
  if (materialId !== null) {
    url += '&material_id=' + materialId;
  }

  console.log(" URL gerada:", url);

  fetch(url)
    .then(res => res.text())
    .then(html => {
      document.getElementById('contentDisplay').innerHTML = html;

      // Reativa o botão de envio do quiz, se existir
      const btn = document.getElementById("enviar-quiz");
      if (btn) {
        btn.addEventListener("click", enviarRespostasQuiz);
      }

      // Reativa o monitoramento dos checkboxes
      if (typeof monitorarChecklist === 'function') {
        monitorarChecklist();
      }
    });
}



// ✅ Função para marcar item como concluído
function marcarConcluido(item_id, elemento) {
  const tempoFinal = Date.now();
  const tempoUtilizado = Math.floor((tempoFinal - tempoInicio) / 1000); // em segundos

  // Atualiza visualmente o ícone
  if (elemento) {
    elemento.classList.add("completed");
    elemento.innerText = "✓";
  }

  // Envia os dados da interação para o backend
  fetch("registrar_interacao.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      item_id: item_id,
      tempo_utilizado: tempoUtilizado,
      concluido: true
    })
  })
  .then(response => response.json())
  .then(data => {
    console.log("Interação registrada com sucesso:", data);
  })
  .catch(error => {
    console.error("Erro ao registrar interação:", error);
  });
}


function carregarSugestoes(item_id) {
  // Exemplo: buscar sugestões relacionadas ao item ou estilo
  fetch(`sugestoes.php?item_id=${item_id}`)
    .then(response => response.json())
    .then(data => {
      const suggestions = document.getElementById("suggestionsScroll");
      if (suggestions) {
        suggestions.innerHTML = "";
        data.forEach(s => {
          const div = document.createElement("div");
          div.className = "suggestion-item";
          div.innerHTML = `<span class="play-icon">▶</span><span class="suggestion-text">${s.titulo}</span><span class="check-icon">○</span>`;
          div.onclick = () => abrirConteudo(s.item_id);
          suggestions.appendChild(div);
        });
      }
    });
}


function alternarConclusao(itemId, el) {
  fetch('marcar_concluido.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'item_id=' + encodeURIComponent(itemId)
  })
  .then(res => res.text())
  .then(status => {
console.log("STATUS RECEBIDO:", JSON.stringify(status));


    if (status === 'concluido') {
      el.innerHTML = '<span style="font-size:18px;">☑</span>';
      el.classList.add('completed');
    } else if (status === 'desfeito') {
      el.innerHTML = '<span style="font-size:18px;">☐</span>';
      el.classList.remove('completed');
    }
  });
}
