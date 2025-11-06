<?php
/* Template Name: Agendamento */
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_servicos =  $wpdb->prefix .'servicos';
$table_agendamentos =  $wpdb->prefix .'agendamentos';


$current_user_email = '';
$current_user_nome  = '';
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $current_user_email = $user->user_email;
    $current_user_nome  = $user->display_name ?: $user->user_login;
}


$is_admin = ($current_user_nome === 'admin_wp' || $current_user_email === 'admin@tinabarros.online');


if(isset($_GET['acao']) && $_GET['acao']==='logout'){
    wp_logout();
    wp_redirect('/');
    exit;
}


if (isset($_GET['acao']) && $_GET['acao']==='json') {
    $rows = $wpdb->get_results("SELECT * FROM $table_agendamentos");
    $events = [];
    foreach($rows as $r){
        if($is_admin){
            $events[] = [
                "title"=> date('H:i', strtotime($r->data_hora_inicio))." - ".date('H:i', strtotime($r->data_hora_fim))." | ".$r->nome_cliente,
                "start"=>$r->data_hora_inicio,
                "end"=>$r->data_hora_fim,
                "color"=>"purple",
                "servicos"=>$r->servicos,
                "nome_cliente"=>$r->nome_cliente,
                "email_cliente"=>$r->email_cliente
            ];
        } else {
            $is_owner = ($r->email_cliente === $current_user_email && $r->nome_cliente === $current_user_nome);
            $color = !$current_user_email ? 'red' : ($is_owner ? 'purple' : 'red');
            $title = $is_owner ? date('H:i', strtotime($r->data_hora_inicio))." - ".date('H:i', strtotime($r->data_hora_fim))." ocupado" : "Horário ocupado";
            $events[] = [
                "title"=>$title,
                "start"=>$r->data_hora_inicio,
                "end"=>$r->data_hora_fim,
                "color"=>$color,
                "servicos"=>$is_owner ? $r->servicos : "",
                "nome_cliente"=>$is_owner ? $r->nome_cliente : "",
                "email_cliente"=>$is_owner ? $r->email_cliente : ""
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($events);
    exit;
}


if (isset($_GET['acao']) && $_GET['acao']==='horarios') {
    $dia = sanitize_text_field($_GET['dia']);
    $weekday = date('N', strtotime($dia)); // 1=segunda ... 7=domingo

    $ocupados = [];
    if($weekday == 1){
        $ocupados[] = [
            "start"=>$dia." 00:00:00",
            "end"=>$dia." 23:59:59",
            "color"=>"red",
            "nome_cliente"=>"Folga",
            "email_cliente"=>"",
            "servicos"=>"Folga"
        ];
    } else {
        $inicio = $dia . " 00:00:00";
        $fim    = $dia . " 23:59:59";
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT data_hora_inicio, data_hora_fim, nome_cliente, email_cliente, servicos FROM $table_agendamentos WHERE data_hora_inicio BETWEEN %s AND %s",
            $inicio, $fim
        ));
        foreach($rows as $r){
            $is_owner = ($r->email_cliente === $current_user_email && $r->nome_cliente === $current_user_nome);
            $color = (!$current_user_email) ? 'red' : ($is_admin || $is_owner ? 'purple' : 'red');
            $ocupados[] = [
                "start"=>$r->data_hora_inicio,
                "end"=>$r->data_hora_fim,
                "color"=>$color,
                "email_cliente"=>$is_admin || $is_owner ? $r->email_cliente : "",
                "nome_cliente"=>$is_admin || $is_owner ? $r->nome_cliente : "",
                "servicos"=>$is_admin || $is_owner ? $r->servicos : ""
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($ocupados);
    exit;
}


if (isset($_POST['acao']) && $_POST['acao']==='salvar') {
    $nome   = sanitize_text_field($_POST['nome']);
    $email  = sanitize_email($_POST['email']);
    $inicio = sanitize_text_field($_POST['hora_inicio']);
    $duracao= intval($_POST['duracao']);
    $fim    = date("Y-m-d H:i:s", strtotime($inicio)+$duracao*60);
    $servicosSelecionados = isset($_POST['servicos']) ? json_decode(stripslashes($_POST['servicos'])) : [];

    $wpdb->insert($table_agendamentos, [
        'nome_cliente'=>$nome,
        'email_cliente'=>$email,
        'data_hora_inicio'=>$inicio,
        'data_hora_fim'=>$fim,
        'servicos'=>implode(',', $servicosSelecionados)
    ]);

    echo json_encode(["status"=>"success","msg"=>"Agendamento confirmado!"]);
    exit;
}

/* 🔹 Serviços */
$servicos = $wpdb->get_results("SELECT * FROM $table_servicos");
?>

<?php wp_head(); ?>
<style>
body { 
    background:#FFEFF; 
    font-family:system-ui,sans-serif; 
    margin:0; 
    padding:0; 
    color:#000; 
}

.header-login { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding:30px 50px; 
    max-width:1200px; 
    margin:0 auto; 
}

.header-login a { 
    font-weight:700; 
    font-size:2rem; 
    text-decoration:none; 
    color:#000; 
}

.header-login nav a { 
    margin-left:20px; 
    font-size:1rem; 
    font-weight:500; 
    text-decoration:none; 
    color:#000; 
    transition:0.3s; 
}

.header-login nav a:hover { 
    text-decoration:underline; 
}

.container { 
    max-width:1200px; 
    margin:30px auto 50px auto; 
    display:grid; 
    grid-template-columns:320px 1fr; 
    gap:20px; 
}

.sidebar { 
    background:#fff; 
    padding:20px; 
    border-radius:20px; 
    box-shadow:0 10px 40px rgba(0,0,0,0.12); 
}

.sidebar h2 { 
    margin-top:0; 
    font-size:22px; 
    margin-bottom:15px; 
}

.sidebar input { 
    width:100%; 
    padding:10px; 
    margin:8px 0; 
    border:1px solid #ddd; 
    border-radius:10px; 
    font-size:14px; 
    box-sizing:border-box; 
}

.servicos-container { 
    display:grid; 
    grid-template-columns:1fr 1fr; 
    gap:10px; 
    margin-top:10px; 
}

.servico-card { 
    padding:10px; 
    border-radius:10px; 
    background:#eee; 
    text-align:center; 
    cursor:pointer; 
    font-weight:500; 
    user-select:none; 
    transition:0.3s; 
}

.servico-card.selected { 
    background:#000; 
    color:#fff; 
}

#calendar { 
    background:#fff; 
    padding:20px; 
    border-radius:20px; 
    min-height:600px; 
    box-shadow:0 10px 40px rgba(0,0,0,0.12); 
}

#caixaHorarios { 
    margin-top:20px; 
    background:#fff; 
    padding:20px; 
    border-radius:20px; 
    display:none; 
    box-shadow:0 10px 40px rgba(0,0,0,0.12); 
}

#horariosDisponiveis { 
    display:grid; 
    grid-template-columns:repeat(3, 1fr); 
    gap:8px; 
    margin-top:15px; 
}

#horariosDisponiveis div { 
    padding:8px; 
    border-radius:8px; 
    cursor:pointer; 
    background:#eee; 
    text-align:center; 
    font-weight:500;
}

#horariosDisponiveis div.ocupado { 
    color:#fff; 
    cursor:not-allowed; 
}

#horariosDisponiveis div.ocupado.red { 
    background:red; 
}

#horariosDisponiveis div.ocupado.purple { 
    background:purple; 
}

#horariosDisponiveis div.selecionado { 
    background:green; 
    color:#fff; 
}

button.agendarBtn { 
    margin-top:10px; 
    background:#000; 
    color:#fff; 
    padding:8px 15px; 
    border:none; 
    border-radius:10px; 
    cursor:pointer; 
    font-size:14px; 
    transition:0.3s; 
}

button.agendarBtn:hover { 
    background:#444; 
}

.popup { 
    position: fixed; 
    top:20px; 
    right:20px; 
    background:#000; 
    color:#fff; 
    padding:15px 25px; 
    border-radius:12px; 
    display:none; 
    z-index:999; 
}

/* Modal bonito e centralizado */
.modal { 
    position: fixed; 
    top:50%; 
    left:50%; 
    transform: translate(-50%,-50%); 
    background:#fdfdfd; 
    padding:25px; 
    border-radius:16px; 
    box-shadow:0 15px 50px rgba(0,0,0,0.3); 
    z-index:10000; 
    display:none; 
    max-width:450px; 
    width:90%; 
    font-family:system-ui,sans-serif; 
    animation:fadeIn 0.3s ease; 
    text-align:center; 
}

.modal h3 { 
    margin-top:0; 
    font-size:20px; 
    color:#222; 
}

.modal .close { 
    float:right; 
    cursor:pointer; 
    font-weight:bold; 
    font-size:18px; 
}

.modal ul { 
    padding-left:0; 
    margin-top:10px; 
    list-style:none; 
}

.modal ul li { 
    margin-bottom:5px; 
    color:#555; 
}

@keyframes fadeIn { 
    from {opacity:0; transform:translate(-50%,-48%);} 
    to {opacity:1; transform:translate(-50%,-50%);} 
}

/* Responsividade */
@media (max-width: 900px) {
    .container {
        grid-template-columns: 1fr; /* uma coluna */
        margin: 20px;
    }
    #calendar {
        min-height: 400px;
    }
    .sidebar {
        margin-bottom: 20px;
    }
}

@media (max-width: 500px) {
    .sidebar input, .servicos-container {
        font-size: 14px;
    }
    .servicos-container {
        grid-template-columns: 1fr; /* servi�os empilhados */
    }
    button.agendarBtn {
        width: 100%; /* bot�o ocupa toda a largura */
    }
}

</style>



<script>window.currentUserEmail='<?php echo esc_js($current_user_email); ?>';</script>

<div class="container">
    <div class="sidebar">
        <h2>Agendar</h2>
        <input type="text" id="nome" placeholder="Nome completo" <?php if($current_user_nome) echo 'value="'.esc_attr($current_user_nome).'" readonly'; ?> >
        <input type="email" id="email" placeholder="Email" <?php if($current_user_email) echo 'value="'.esc_attr($current_user_email).'" readonly'; ?> >

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

<script>
<?php get_footer(); ?>
