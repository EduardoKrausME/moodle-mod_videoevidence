<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * videoevidence.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['addevidence'] = 'Adicionar evidência';
$string['addquestion'] = 'Adicionar pergunta';
$string['allowseek'] = 'Permitir avanço livre';
$string['answerlocked'] = 'Esta resposta já foi avaliada e está bloqueada.';
$string['backtoactivity'] = 'Voltar para a atividade';
$string['backtoreport'] = 'Voltar ao relatório';
$string['completiondetail:evidence'] = 'Enviar todas as perguntas obrigatórias de evidência';
$string['completionevidence'] = 'Exigir o envio de todas as perguntas obrigatórias de evidência';
$string['editquestion'] = 'Editar pergunta';
$string['endbeforestart'] = 'O tempo final não pode ser anterior ao inicial.';
$string['endtime'] = 'Fim';
$string['evidencerequirement'] = 'Selecione no mínimo {$a->min} e no máximo {$a->max} evidência(s).';
$string['evidenceselected'] = 'Evidências selecionadas';
$string['expectedboth'] = 'Defina início e fim da faixa esperada ou deixe ambos vazios.';
$string['expectedend'] = 'Fim da faixa esperada (opcional)';
$string['expectedstart'] = 'Início da faixa esperada (opcional)';
$string['feedback'] = 'Feedback';
$string['grade'] = 'Avaliar';
$string['gradessaved'] = 'Avaliações salvas.';
$string['invaliddirecturl'] = 'Use uma URL direta .mp4, .webm, .ogv, .m4v, .mov ou .m3u8.';
$string['invalidmaximum'] = 'O máximo deve ser maior ou igual ao mínimo.';
$string['invalidminimum'] = 'O mínimo deve ser pelo menos 1.';
$string['invalidtimecode'] = 'Use segundos, MM:SS ou HH:MM:SS.';
$string['invalidtolerance'] = 'A tolerância não pode ser negativa.';
$string['invalidvideourl'] = 'Informe uma URL HTTP/HTTPS válida.';
$string['invalidvimeourl'] = 'Informe uma URL válida do Vimeo.';
$string['invalidweight'] = 'O peso deve estar entre 0 e 100.';
$string['invalidyoutubeurl'] = 'Informe uma URL válida do YouTube.';
$string['justification'] = 'Por que este trecho serve como evidência?';
$string['justificationscore'] = 'Nota da justificativa';
$string['justificationweight'] = 'Peso da justificativa (%)';
$string['managequestions'] = 'Gerenciar perguntas';
$string['maxevidence'] = 'Quantidade máxima de evidências';
$string['maxevidencereached'] = 'A quantidade máxima de evidências desta pergunta foi atingida.';
$string['minevidence'] = 'Quantidade mínima de evidências';
$string['modulename'] = 'Video Evidence';
$string['modulename_help'] = 'O estudante responde perguntas selecionando evidências dentro do vídeo e justificando por que o momento ou intervalo sustenta a resposta.';
$string['modulenameplural'] = 'Atividades Video Evidence';
$string['noactivities'] = 'Não há atividades Video Evidence neste curso.';
$string['noevidence'] = 'Nenhuma evidência selecionada.';
$string['noquestions'] = 'Nenhuma pergunta de evidência foi criada.';
$string['nostudents'] = 'Nenhum aluno matriculado encontrado.';
$string['notenoughevidence'] = 'Adicione a quantidade mínima de evidências antes de enviar.';
$string['pluginadministration'] = 'Administração do Video Evidence';
$string['pluginname'] = 'Video Evidence';
$string['poster'] = 'Imagem de capa';
$string['privacy:metadata:answers'] = 'Armazena o estado e a avaliação das respostas dos estudantes.';
$string['privacy:metadata:answers:feedback'] = 'Feedback do professor.';
$string['privacy:metadata:answers:finalscore'] = 'Nota final da resposta.';
$string['privacy:metadata:answers:status'] = 'Estado da resposta.';
$string['privacy:metadata:answers:userid'] = 'Usuário dono da resposta.';
$string['privacy:metadata:evidence'] = 'Armazena as seleções de evidência do vídeo.';
$string['privacy:metadata:evidence:endtime'] = 'Fim da evidência.';
$string['privacy:metadata:evidence:justification'] = 'Justificativa escrita pelo estudante.';
$string['privacy:metadata:evidence:starttime'] = 'Início da evidência.';
$string['privacy:metadata:progress'] = 'Armazena o acompanhamento de reprodução do vídeo.';
$string['privacy:metadata:progress:lastposition'] = 'Última posição salva.';
$string['privacy:metadata:progress:percent'] = 'Percentual único assistido.';
$string['privacy:metadata:progress:userid'] = 'Usuário cujo progresso é armazenado.';
$string['privacy:metadata:progress:watchedsegments'] = 'Trechos realmente assistidos.';
$string['quantityscore'] = 'Nota da quantidade';
$string['quantityweight'] = 'Peso da quantidade mínima (%)';
$string['questiondeleted'] = 'Pergunta excluída.';
$string['questionnumber'] = 'Pergunta {$a}';
$string['questionsanswered'] = 'Perguntas respondidas';
$string['questionsaved'] = 'Pergunta salva.';
$string['questiontext'] = 'Pergunta';
$string['report'] = 'Relatório';
$string['required'] = 'Obrigatória';
$string['requiredquestion'] = 'Obrigatória para conclusão da atividade';
$string['resumeask'] = 'Perguntar antes de retomar';
$string['resumeautomatic'] = 'Retomar automaticamente';
$string['resumefromstart'] = 'Iniciar do começo';
$string['resumeplayback'] = 'Retomada da reprodução';
$string['resumequestion'] = 'Você parou em {$a}. Deseja continuar desse ponto?';
$string['savegrades'] = 'Salvar avaliações';
$string['score'] = 'Nota';
$string['selectioninterval'] = 'Intervalo de tempo';
$string['selectionmoment'] = 'Momento único';
$string['selectionscore'] = 'Nota da seleção';
$string['selectiontype'] = 'Tipo de seleção da evidência';
$string['selectionweight'] = 'Peso da seleção correta (%)';
$string['sourceupload'] = 'Upload para o Moodle';
$string['sourceurl'] = 'URL direta / HLS';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['starttime'] = 'Início';
$string['statusdraft'] = 'Rascunho';
$string['statusgraded'] = 'Avaliada';
$string['statussubmitted'] = 'Enviada';
$string['student'] = 'Aluno';
$string['submitanswer'] = 'Enviar resposta';
$string['submitted'] = 'Entregues';
$string['timeline'] = 'Linha do tempo assistida e marcadores de evidência';
$string['tolerance'] = 'Tolerância em segundos';
$string['usecurrenttime'] = 'Usar tempo atual como início';
$string['usecurrenttimeend'] = 'Usar tempo atual como fim';
$string['videoevidence:addinstance'] = 'Adicionar atividade Video Evidence';
$string['videoevidence:grade'] = 'Avaliar respostas do Video Evidence';
$string['videoevidence:managequestions'] = 'Gerenciar perguntas do Video Evidence';
$string['videoevidence:view'] = 'Visualizar atividade Video Evidence';
$string['videoevidence:viewreport'] = 'Visualizar relatórios do Video Evidence';
$string['videoevidencename'] = 'Nome da atividade';
$string['videofile'] = 'Arquivo de vídeo';
$string['videoheader'] = 'Vídeo';
$string['videosource'] = 'Fonte do vídeo';
$string['videourl'] = 'URL do vídeo';
$string['watchedpercent'] = 'Percentual assistido';
