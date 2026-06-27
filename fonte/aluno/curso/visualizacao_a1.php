<?php
// visualizacao_a1.php
header('Content-Type: application/json; charset=utf-8');

require '../conexao.php';
session_start();

// Ajuste se o nome da sessão for outro:
$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    echo json_encode([
        'error' => 'Usuario não autenticado na sessão.'
    ]);
    exit;
}

// Por enquanto estamos fixando no Quiz da Aula 1
$quiz_id = 1;

// Query baseada no SELECT que você testou
$sql = "
SELECT
  k.id AS conhecimento_id,
  k.codigo AS conhecimento_codigo,
  k.descricao AS conhecimento_descricao,

  COUNT(DISTINCT pc.pergunta_id) AS total_perguntas_relacionadas,

  SUM(CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END) AS total_respostas_aluno,

  SUM(
    CASE
      WHEN r.id IS NOT NULL AND qo.correta = 1 THEN 1
      ELSE 0
    END
  ) AS total_respostas_certas,

  SUM(
    CASE
      WHEN r.id IS NOT NULL AND qo.correta = 0 THEN 1
      ELSE 0
    END
  ) AS total_respostas_erradas,

  CASE
    WHEN COUNT(DISTINCT pc.pergunta_id) = 0 THEN 'NAO_AVALIADO'
    WHEN SUM(CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END) = 0 THEN 'NAO_AVALIADO'
    WHEN SUM(
           CASE
             WHEN r.id IS NOT NULL AND qo.correta = 0 THEN 1
             ELSE 0
           END
         ) > 0 THEN 'PONTO_ATENCAO'
    WHEN SUM(
           CASE
             WHEN r.id IS NOT NULL AND qo.correta = 1 THEN 1
             ELSE 0
           END
         ) = COUNT(DISTINCT pc.pergunta_id)
         AND COUNT(DISTINCT pc.pergunta_id) > 0
         THEN 'COMPREENDIDO'
    ELSE 'NAO_AVALIADO'
  END AS status_conhecimento

FROM conhecimentos k
JOIN pergunta_conhecimento pc
  ON pc.conhecimento_id = k.id

JOIN quiz_perguntas qp
  ON qp.id = pc.pergunta_id
 AND qp.quiz_id = ?

LEFT JOIN respostas_quiz r
  ON r.pergunta_id = qp.id
 AND r.quiz_id     = ?
 AND r.usuario_id  = ?

LEFT JOIN quiz_opcoes qo
  ON qo.id = r.opcao_id

GROUP BY
  k.id, k.codigo, k.descricao

ORDER BY
  k.codigo
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iii', $quiz_id, $quiz_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {

    // Mapeia para os rótulos usados no JS: OK / ATENCAO / NEUTRO
    $status = 'NEUTRO';
    if ($row['status_conhecimento'] === 'COMPREENDIDO') {
        $status = 'OK';
    } elseif ($row['status_conhecimento'] === 'PONTO_ATENCAO') {
        $status = 'ATENCAO';
    } elseif ($row['status_conhecimento'] === 'NAO_AVALIADO') {
        $status = 'NEUTRO';
    }

    $dados[] = [
        'codigo'        => $row['conhecimento_codigo'],
        'descricao'     => $row['conhecimento_descricao'],
        'status'        => $status,
        'status_bruto'  => $row['status_conhecimento'],
        'total_perguntas'   => (int)$row['total_perguntas_relacionadas'],
        'total_respostas'   => (int)$row['total_respostas_aluno'],
        'total_certas'      => (int)$row['total_respostas_certas'],
        'total_erradas'     => (int)$row['total_respostas_erradas'],
    ];
}

echo json_encode($dados);

