<?php
// item_quiz.php

require '../conexao.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

// Verifica se o item tem aula_id
$aula_id = $item['aula_id'] ?? null;

if (!$aula_id) {
    $stmtConteudo = $conn->prepare("SELECT conteudo_id FROM itens_aula WHERE id = ?");
    $stmtConteudo->bind_param("i", $item['id']);
    $stmtConteudo->execute();
    $resConteudo = $stmtConteudo->get_result();
    $linhaConteudo = $resConteudo->fetch_assoc();
    $conteudo_id = $linhaConteudo['conteudo_id'] ?? null;

    $aula_id = null;
    if ($conteudo_id) {
        $stmtAula = $conn->prepare("SELECT aula_id FROM conteudos WHERE id = ?");
        $stmtAula->bind_param("i", $conteudo_id);
        $stmtAula->execute();
        $resAula = $stmtAula->get_result();
        $linhaAula = $resAula->fetch_assoc();
        $aula_id = $linhaAula['aula_id'] ?? null;
    }
}

if (!$aula_id) {
    echo "<p>Não foi possível identificar a aula para este item.</p>";
    return;
}

// Busca quiz pela aula
$resultQuiz = $conn->query("SELECT id FROM quizzes WHERE aula_id = {$aula_id}");
$linhaQuiz = $resultQuiz->fetch_assoc();
$quiz_id = $linhaQuiz['id'] ?? null;

if (!$quiz_id) {
    echo "<p>Quiz não configurado para esta aula.</p>";
    return;
}

// Dados do quiz
$quiz = null;
$stmtQuiz = $conn->prepare("SELECT titulo, instrucoes FROM quizzes WHERE id = ?");
$stmtQuiz->bind_param("i", $quiz_id);
$stmtQuiz->execute();
$resQuiz = $stmtQuiz->get_result();
$quiz = $resQuiz->fetch_assoc();

// Buscar respostas anteriores do aluno
$respostas = [];
$stmtResp = $conn->prepare("SELECT pergunta_id, opcao_id FROM respostas_quiz WHERE usuario_id = ? AND quiz_id = ?");
$stmtResp->bind_param("ii", $usuario_id, $quiz_id);
$stmtResp->execute();
$resResp = $stmtResp->get_result();

while ($linhaResp = $resResp->fetch_assoc()) {
    $respostas[$linhaResp['pergunta_id']] = $linhaResp['opcao_id'];
}

// Buscar perguntas
$resultPerguntas = $conn->query("
  SELECT id, enunciado
  FROM quiz_perguntas
  WHERE quiz_id = {$quiz_id}
  ORDER BY ordem
");

$perguntas = [];
while ($row = $resultPerguntas->fetch_assoc()) {
    $perguntas[] = $row;
}

if (empty($perguntas)) {
    echo "<p>Este quiz ainda não possui perguntas cadastradas.</p>";
    return;
}

// Buscar opções de cada pergunta
foreach ($perguntas as &$p) {
    $resultOpcoes = $conn->query("
      SELECT id, texto
      FROM quiz_opcoes
      WHERE pergunta_id = {$p['id']}
      ORDER BY ordem
    ");

    $p['opcoes'] = [];
    while ($op = $resultOpcoes->fetch_assoc()) {
        $p['opcoes'][] = $op;
    }
}

/**
 * Renderiza o enunciado com suporte a <CODE>...</CODE>
 * - Texto fora de <CODE> é renderizado com nl2br + escape
 * - Trecho dentro de <CODE> vai para <pre class="quiz-code-block"> preservando indentação
 */
function renderizarEnunciado($texto, $numeroQuestao)
{
    $texto = $texto ?? '';

    // Divide em blocos alternando texto / tags <CODE> / </CODE>
    $partes = preg_split('/(<CODE>|<\/CODE>)/i', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);

    $emCodigo = false;
    $saida = "<p style='white-space:pre-wrap;'><b>" . htmlspecialchars($numeroQuestao) . "</b> ";

    foreach ($partes as $parte) {
        if (strcasecmp($parte, '<CODE>') === 0) {
            // Fecha parágrafo atual e abre bloco de código
            $emCodigo = true;
            $saida .= "</p><pre class='quiz-code-block'>";
            continue;
        }
        if (strcasecmp($parte, '</CODE>') === 0) {
            // Fecha bloco de código e reabre parágrafo
            $emCodigo = false;
            $saida .= "</pre><p style='white-space:pre-wrap;'>";
            continue;
        }

        if ($emCodigo) {
            // Dentro do bloco de código: preserva exatamente
            $saida .= htmlspecialchars($parte);
        } else {
            // Texto normal: quebras de linha e escape
            $saida .= nl2br(htmlspecialchars($parte));
        }
    }

    $saida .= "</p>";
    return $saida;
}

/**
 * Renderiza o texto de uma opção com suporte a <CODE>...</CODE>
 * - Permite texto antes/depois do código
 * - Pode ter mais de um bloco de código se quiser
 */
function renderizarOpcao($texto)
{
    $texto = $texto ?? '';

    // Remove quebras de linha iniciais para não criar “linha em branco”
    $texto = ltrim($texto, "\r\n");


    $partes = preg_split('/(<CODE>|<\/CODE>)/i', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);
    $emCodigo = false;
    $saida = "";

    foreach ($partes as $parte) {
        if (strcasecmp($parte, '<CODE>') === 0) {
            $emCodigo = true;
            $saida .= "<pre class='quiz-code-block'>";
            continue;
        }
        if (strcasecmp($parte, '</CODE>') === 0) {
            $emCodigo = false;
            $saida .= "</pre>";
            continue;
        }
  
        if ($emCodigo) {
            $saida .= htmlspecialchars($parte);
        } else {
            $saida .= nl2br(htmlspecialchars($parte));
        }
    }

    return $saida;
}
?>

<style>
.quiz-container {
  padding: 16px 20px !important;
  background-color: #fdfdfd !important;
  border: 1px solid #ccc !important;
  border-radius: 8px !important;

  /* ocupa toda a largura disponível do contentDisplay */
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box;

  /* tira aquelas grandes “bordas” laterais */
  margin: 10px 0 !important;

  font-family: Arial, sans-serif !important;
}

#quiz-form {
  width: 700px !important;   /* ou 650px se preferir */
  max-width: 100%;
  margin: 0 auto;            /* centraliza */
  box-sizing: border-box;
}



.quiz-instrucoes {
  margin-bottom: 20px !important;
  font-size: 16px !important;
  color: #333 !important;
}

.pergunta {
  margin-bottom: 25px !important;
}

.pergunta p {
  font-weight: bold !important;
  margin-bottom: 10px !important;
}

/* Bloco de código dentro do enunciado / opções */
.quiz-code-block {
  background-color: #f7f7f7;
  border: 1px solid #ccc;
  border-radius: 6px;
  padding: 8px 10px;
  font-family: Consolas, "Courier New", monospace;
  font-size: 14px;
  white-space: pre; /* preserva espaços e quebras exatamente */
  margin: 6px 0 4px 0;
  width: 100%;
  box-sizing: border-box; /* garante que não “estoure” a borda da opção */
}

.opcao {
  display: flex !important;
  align-items: flex-start !important;
  gap: 10px !important;
  margin-bottom: 12px !important;
  padding: 10px 14px !important;
  background-color: #fff !important;
  border: 1px solid #ddd !important;
  border-radius: 6px !important;
  cursor: pointer !important;
  transition: background-color 0.2s ease !important;
  width: 100%;
  box-sizing: border-box;
}

.opcao:hover {
  background-color: #eef6ff !important;
}

.opcao input[type="radio"] {
  width: auto !important;
  margin: 0 !important;
  padding: 0 !important;
  appearance: auto !important;
  position: static !important;
  visibility: visible !important;
  opacity: 1 !important;
  margin-top: 3px; /* NOVO: desce um pouquinho o círculo */
}

.quiz-pos-texto {
  margin-top: 30px !important;
  font-size: 15px !important;
  color: #555 !important;
}

#enviar-quiz {
  display: block !important;
  margin: 30px auto 0 !important;
  padding: 10px 20px !important;
  font-size: 16px !important;
  background-color: #007bff !important;
  color: white !important;
  border: none !important;
  border-radius: 6px !important;
  cursor: pointer !important;
  transition: background-color 0.2s ease !important;
}

#enviar-quiz:hover {
  background-color: #0056b3 !important;
}
</style>

<div class="quiz-container" id="quiz-container">
  <?php if (!empty($quiz['instrucoes'])): ?>
    <div class="quiz-instrucoes" id="quiz-instrucoes">
      <?= nl2br($quiz['instrucoes']) ?>
    </div>
  <?php endif; ?>

  <form id="quiz-form">
    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">

    <?php $contador = 1; ?>
    <?php foreach ($perguntas as &$p): ?>
      <div class="pergunta" id="pergunta_<?= $p['id'] ?>">

        <?= renderizarEnunciado($p['enunciado'] ?? '', $contador . '. ') ?>

        <?php foreach ($p['opcoes'] as $o): ?>
          <?php
            $checked = '';
            if (isset($respostas[$p['id']]) && $respostas[$p['id']] == $o['id']) {
                $checked = 'checked';
            }
            $textoOpcao = $o['texto'] ?? '';
          ?>
          <label class="opcao" id="opcao_<?= $o['id'] ?>">
            <input type="radio"
                   name="pergunta_<?= $p['id'] ?>"
                   value="<?= $o['id'] ?>"
                   <?= $checked ?>>

            <span style="display:block;">
              <?= renderizarOpcao($textoOpcao) ?>
            </span>
          </label>
        <?php endforeach; ?>

      </div>
      <?php $contador++; ?>
    <?php endforeach; ?>

    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
    <input type="hidden" name="finalizar_quiz" value="1">
  </form>

  <?php if (!empty($item['texto_pos_atividade'])): ?>
    <div class="quiz-pos-texto" id="quiz-pos-texto">
      <?= nl2br($item['texto_pos_atividade']) ?>
    </div>
  <?php endif; ?>

  <button id="enviar-quiz" type="button">Enviar</button>
</div>
