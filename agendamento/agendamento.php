<?php
/* Template Name: Agendamento */
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_servicos = $wpdb->prefix . 'servicos';
$table_agendamentos = $wpdb->prefix . 'agendamentos';

$current_user_email = '';
$current_user_nome = '';
$is_admin = false;

if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $current_user_email = $user->user_email;
    $current_user_nome = $user->display_name ?: $user->user_login;
    $is_admin = ($current_user_nome === 'admin_wp' || $current_user_email === 'admin@tinabarros.online');
}

if (isset($_GET['acao']) && $_GET['acao'] === 'logout') {
    wp_logout();
    wp_redirect('/');
    exit;
}

/* Eventos JSON para FullCalendar */
if (isset($_GET['acao']) && $_GET['acao'] === 'json') {
    $events = [];
    if ($is_admin) {
        $rows = $wpdb->get_results("SELECT * FROM $table_agendamentos");
    } elseif ($current_user_email) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_agendamentos WHERE email_cliente=%s AND nome_cliente=%s",
            $current_user_email,
            $current_user_nome
        ));
    } else {
        $rows = [];
    }

    foreach ($rows as $r) {
        $events[] = [
            "title" => date('H:i', strtotime($r->data_hora_inicio)) . " - " . date('H:i', strtotime($r->data_hora_fim)),
            "start" => $r->data_hora_inicio,
            "end" => $r->data_hora_fim,
            "color" => "purple",
            "servicos" => $r->servicos,
            "nome_cliente" => $r->nome_cliente,
            "email_cliente" => $r->email_cliente
        ];
    }

    wp_send_json($events);
}

/* Horários disponíveis do dia */
if (isset($_GET['acao']) && $_GET['acao'] === 'horarios') {
    $dia = sanitize_text_field($_GET['dia']);
    $weekday = date('N', strtotime($dia));
    $ocupados = [];

    if ($weekday == 1) {
        $ocupados[] = [
            "start" => $dia . " 00:00:00",
            "end" => $dia . " 23:59:59",
            "color" => "red",
            "nome_cliente" => "Folga",
            "email_cliente" => "",
            "servicos" => "Folga"
        ];
    } else {
        $inicio = $dia . " 00:00:00";
        $fim = $dia . " 23:59:59";
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT data_hora_inicio, data_hora_fim FROM $table_agendamentos WHERE data_hora_inicio BETWEEN %s AND %s",
            $inicio, $fim
        ));
        foreach ($rows as $r) {
            $ocupados[] = [
                "start" => $r->data_hora_inicio,
                "end" => $r->data_hora_fim,
                "color" => "red",
                "email_cliente" => "",
                "nome_cliente" => "",
                "servicos" => ""
            ];
        }
    }

    wp_send_json($ocupados);
}

/* Salvar agendamento */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar') {
    $nome = sanitize_text_field($_POST['nome']);
    $email = sanitize_email($_POST['email']);

    if (empty($nome) || empty($email)) {
        wp_send_json(["status" => "error", "msg" => "Nome e email são obrigatórios."]);
    }

    $inicio = sanitize_text_field($_POST['hora_inicio']);
    $duracao = intval($_POST['duracao']);
    $fim = date("Y-m-d H:i:s", strtotime($inicio) + $duracao * 60);
    $servicosSelecionados = isset($_POST['servicos']) ? json_decode(stripslashes($_POST['servicos'])) : [];

    $existe = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_agendamentos WHERE data_hora_inicio=%s",
        $inicio
    ));

    if ($existe) {
        wp_send_json(["status" => "error", "msg" => "Horário já ocupado."]);
    }

    $wpdb->insert($table_agendamentos, [
        'nome_cliente' => $nome,
        'email_cliente' => $email,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $fim,
        'servicos' => implode(',', $servicosSelecionados)
    ]);

    wp_send_json(["status" => "success", "msg" => "Agendamento confirmado!"]);
}

/* Serviços */
$servicos = $wpdb->get_results("SELECT * FROM $table_servicos");
?>

<?php wp_head(); ?>

<script>
    window.currentUserNome = <?php echo json_encode($current_user_nome); ?>;
    window.currentUserEmail = <?php echo json_encode($current_user_email); ?>;
    window.isAdmin = <?php echo json_encode($is_admin); ?>;
</script>



<div class="container">
    <div class="sidebar">
        <h2>Agendar</h2>

        <?php if (!is_user_logged_in()): ?>
            <div id="loginPrompt" style="background:#FDEBD0; padding:20px; border-radius:12px; text-align:center;">
                <p>Para agendar, crie uma conta ou faça login.</p>
                <button class="btn-register" onclick="window.location.href='/conta'">Registrar</button>
                <button class="btn-login" onclick="window.location.href='/conta'">Login</button>

            </div>
        <?php else: ?>
            <input type="text" id="nome" placeholder="Nome completo" value="<?php echo esc_attr($current_user_nome); ?>" readonly>
            <input type="email" id="email" placeholder="Email" value="<?php echo esc_attr($current_user_email); ?>" readonly>

            <h3>Serviços:</h3>
            <div class="servicos-container">
                <?php foreach($servicos as $s): ?>
                    <div class="servico-card" data-nome="<?= esc_attr($s->nome) ?>" data-duracao="<?= $s->duracao ?>">
                        <?= esc_html($s->nome) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <p><strong>Tempo Total:</strong> <span id="tempoTotal">0</span> min</p>

            <div id="caixaHorarios">
                <h3>Escolher horário</h3>
                <p id="diaEscolhido"></p>
                <div id="horariosDisponiveis"></div>
                <button class="agendarBtn" onclick="confirmarAgendamento()">Agendar</button>
            </div>
        <?php endif; ?>
    </div>

    <div id="calendar"></div>
</div>

<div class="popup" id="popup"></div>

<div class="modal" id="modalAgendamento">
    <span class="close" onclick="document.getElementById('modalAgendamento').style.display='none'">×</span>
    <h3>Agendamento</h3>
    <p id="modalHorario"></p>
    <p id="modalNomeCliente"></p>
    <p id="modalEmailCliente"></p>
    <ul id="modalServicos"></ul>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<?php wp_footer(); ?>
