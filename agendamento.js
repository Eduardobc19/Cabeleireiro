let horaInicioSelecionada = null,
    horaFimSelecionada = null,
    diaSelecionado = null,
    duracaoSelecionada = 0,
    servicosSelecionados = [],
    diaSelecionadoCelula = null,
    agendamentosFixos = [],
    calendar,
    agendamentoEmAndamento = false; // Bloqueio global

document.addEventListener('DOMContentLoaded', () => {
    const nomeInput = document.getElementById('nome');
    const emailInput = document.getElementById('email');

    if (window.currentUserNome && nomeInput) {
        nomeInput.value = window.currentUserNome;
        nomeInput.readOnly = true;
    }
    if (window.currentUserEmail && emailInput) {
        emailInput.value = window.currentUserEmail;
        emailInput.readOnly = true;
    }

    iniciarCalendar();

    window.addEventListener('resize', () => {
        if (calendar) calendar.updateSize();
    });

    const btnAgendar = document.querySelector('.agendarBtn');
    if (btnAgendar) btnAgendar.addEventListener('click', confirmarAgendamento);
});

function showPopup(msg, color = "#000", duration = 3000) {
    const popup = document.getElementById('popup');
    popup.textContent = msg;
    popup.style.background = color;
    popup.style.opacity = "1";
    popup.style.display = 'block';

    if (popup.fadeTimeout) clearTimeout(popup.fadeTimeout);

    popup.fadeTimeout = setTimeout(() => {
        popup.style.display = 'none';
    }, duration);
}

function mostrarModalAgendamento(ag) {
    document.getElementById('modalHorario').textContent =
        `Horário: ${formatTime(new Date(ag.start))} - ${formatTime(new Date(ag.end))}`;
    document.getElementById('modalNomeCliente').textContent =
        ag.nome_cliente ? `Cliente: ${ag.nome_cliente}` : '';
    document.getElementById('modalEmailCliente').textContent =
        ag.email_cliente ? `Email: ${ag.email_cliente}` : '';

    const ul = document.getElementById('modalServicos');
    ul.innerHTML = '';
    if (ag.servicos) {
        ag.servicos.split(',').forEach(s => {
            const li = document.createElement('li');
            li.textContent = s.trim();
            ul.appendChild(li);
        });
    }

    document.getElementById('modalAgendamento').style.display = 'block';
}

document.addEventListener('click', e => {
    if (e.target.classList.contains('servico-card')) {
        e.target.classList.toggle('selected');
        atualizarTempo();
    }
});

function atualizarTempo() {
    servicosSelecionados = [];
    let total = 0;

    document.querySelectorAll('.servico-card.selected').forEach(c => {
        total += parseInt(c.dataset.duracao);
        servicosSelecionados.push(c.dataset.nome);
    });

    document.getElementById('tempoTotal').textContent = total;
    duracaoSelecionada = total || 60;

    if (diaSelecionado) gerarHorarios(diaSelecionado, duracaoSelecionada);
}

function iniciarCalendar() {
    if (typeof FullCalendar === 'undefined') {
        console.error("FullCalendar não carregado");
        return;
    }

    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        buttonText: { today: 'Hoje' },
        events: window.location.href + '?acao=json',

        dayCellClassNames: function(arg) {
            if (arg.date.getDay() === 1) return ['segunda-feira'];
            return [];
        },

        dateClick(info) {
            if (diaSelecionadoCelula) diaSelecionadoCelula.style.outline = "";
            diaSelecionadoCelula = info.dayEl;
            diaSelecionadoCelula.style.outline = "3px solid #00aaff";
            diaSelecionado = info.dateStr;
            duracaoSelecionada =
                parseInt(document.getElementById('tempoTotal').textContent) || 60;

            document.getElementById('diaEscolhido').textContent =
                "Dia: " + diaSelecionado;
            document.getElementById('caixaHorarios').style.display = 'block';

            carregarAgendamentosFixos();
        },

        eventClick(info) {
            mostrarModalAgendamento({
                start: info.event.start,
                end: info.event.end,
                color: info.event.backgroundColor,
                nome_cliente: info.event.extendedProps.nome_cliente || '',
                email_cliente: info.event.extendedProps.email_cliente || '',
                servicos: info.event.extendedProps.servicos || ''
            });
        },

        windowResize() {
            calendar.updateSize();
        }
    });

    calendar.render();

    // Atualização periódica protegida
    setInterval(() => {
        if (agendamentoEmAndamento) return; // Bloqueia enquanto salva
        if (calendar) calendar.refetchEvents();
        if (diaSelecionado) carregarAgendamentosFixos();
    }, 30000);
}

function carregarAgendamentosFixos() {
    fetch(`?acao=horarios&dia=${encodeURIComponent(diaSelecionado)}`)
        .then(r => r.json())
        .then(data => {
            agendamentosFixos = data.map(o => ({
                start: new Date(o.start),
                end: new Date(o.end),
                color: o.color,
                email_cliente: o.email_cliente,
                nome_cliente: o.nome_cliente,
                servicos: o.servicos
            }));
            gerarHorarios(diaSelecionado, duracaoSelecionada);
        })
        .catch(err => console.error("Erro ao carregar horários:", err));
}

function gerarHorarios(dia, duracao) {
    horaInicioSelecionada = null;
    horaFimSelecionada = null;

    const container = document.getElementById('horariosDisponiveis');
    container.innerHTML = '';

    const weekday = new Date(dia).getDay();

    // Segunda-feira = Folga
    if (weekday === 1) {
        const div = document.createElement('div');
        div.textContent = 'Dia de folga';
        div.classList.add('ocupado', 'folga');
        div.style.background = '#C0392B';
        div.style.color = '#fff';
        div.style.cursor = 'not-allowed';
        div.onclick = () => showPopup('Dia de folga', 'red');
        container.appendChild(div);
        return;
    }

    // Horários ocupados
    agendamentosFixos.forEach(a => {
        const div = document.createElement('div');
        if (a.nome_cliente && a.nome_cliente !== 'Folga') {
            div.textContent =
                window.isAdmin ||
                (a.email_cliente === window.currentUserEmail &&
                    a.nome_cliente === window.currentUserNome)
                    ? `${formatTime(a.start)} - ${formatTime(a.end)} | ${a.nome_cliente}`
                    : 'Horário ocupado';
        } else if (a.nome_cliente === 'Folga') {
            div.textContent = 'Folga';
        }
        div.classList.add('ocupado', a.color);
        div.onclick = () => mostrarModalAgendamento(a);
        container.appendChild(div);
    });

    // Períodos disponíveis
    let periodos = [];
    if (weekday >= 2 && weekday <= 5)
        periodos = [['09:00', '12:30'], ['14:00', '19:00']];
    else if (weekday === 6)
        periodos = [['08:30', '13:00'], ['14:00', '20:00']];
    else if (weekday === 0)
        periodos = [['08:30', '12:00'], ['13:00', '17:30']];

    periodos.forEach(p => {
        let startH = new Date(`${dia}T${p[0]}:00`);
        const endH = new Date(`${dia}T${p[1]}:00`);

        while (startH.getTime() + duracao * 60 * 1000 <= endH.getTime()) {
            const blocoLivre = new Date(startH);
            const fimBloco = new Date(startH.getTime() + duracao * 60 * 1000);

            const overlap = agendamentosFixos.some(a =>
                blocoLivre.getTime() < a.end.getTime() &&
                fimBloco.getTime() > a.start.getTime()
            );

            if (!overlap) {
                const div = document.createElement('div');
                div.textContent = `${formatTime(blocoLivre)} - ${formatTime(fimBloco)}`;
                div.classList.add('livre');
                div.onclick = () => {
                    horaInicioSelecionada = blocoLivre;
                    horaFimSelecionada = fimBloco;
                    servicosSelecionados =
                        Array.from(document.querySelectorAll('.servico-card.selected')).map(c => c.dataset.nome);
                    container.querySelectorAll('div').forEach(d => d.classList.remove('selecionado'));
                    div.classList.add('selecionado');
                };
                container.appendChild(div);
            }

            startH = new Date(startH.getTime() + 30 * 60 * 1000);
        }
    });
}

function confirmarAgendamento() {
    if (!horaInicioSelecionada || servicosSelecionados.length === 0) {
        showPopup("Selecione horário e serviços", "red");
        return;
    }

    if (agendamentoEmAndamento) return; // Bloqueio extra
    agendamentoEmAndamento = true;

    const btnAgendar = document.querySelector('.agendarBtn');
    btnAgendar.disabled = true;

    const payload = {
        acao: 'salvar',
        nome: document.getElementById('nome').value.trim(),
        email: document.getElementById('email').value.trim(),
        hora_inicio: horaInicioSelecionada.toISOString().slice(0, 19).replace('T', ' '),
        duracao: duracaoSelecionada,
        servicos: JSON.stringify(servicosSelecionados)
    };

    fetch("", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(payload)
    })
        .then(r => r.json())
        .then(res => {
            const popupDuration = 2500;
            if (res.status === "success") {
                showPopup(res.msg, "green", popupDuration);
                setTimeout(() => {
                    if (calendar) calendar.refetchEvents();
                    carregarAgendamentosFixos();
                    btnAgendar.disabled = false;
                    agendamentoEmAndamento = false;
                }, popupDuration);
            } else {
                showPopup(res.msg || "Erro ao agendar", "red", popupDuration);
                setTimeout(() => {
                    btnAgendar.disabled = false;
                    agendamentoEmAndamento = false;
                }, popupDuration);
            }
        })
        .catch(err => {
            console.error(err);
            showPopup("Erro ao comunicar com o servidor", "red", 2500);
            setTimeout(() => {
                btnAgendar.disabled = false;
                agendamentoEmAndamento = false;
            }, 2500);
        });
}

function formatTime(date) {
    return date.toLocaleTimeString('pt-PT', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
}
