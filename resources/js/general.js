window.ajax = function (url, data, callBack = null) {
    fetch(url, {
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json, text-plain, */*",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": document
            .querySelector('input[name="_token"]')
            .value,
        },
        method: "post",
        credentials: "same-origin",
        body: JSON.stringify(data),
    })
        .then((response) => response.json())
        .then((response) => {
            if (callBack) callBack(response);
            // console.log (callBack);
        })
        .catch(function (error) {
            console.log(error);
        });
};

window.modal = function (
    action,
    title = null,
    text = null,
    button1 = null,
    button2 = null
) {
    let modal = document.getElementById("modal");

    let modalTitle = modal.querySelector(".modal-title");
    let modalText = modal.querySelector(".modal-text");
    let modalButtons = modal.querySelector(".modal-buttons");
    let modalButton1 = modal.querySelector(".modal-button1");
    let modalButton2 = modal.querySelector(".modal-button2");

    if (action == "open") {
        if (modal.classList.contains("hide")) modal.classList.remove("hide");
        if (title) {
            if (modalTitle.classList.contains("hide"))
                modalTitle.classList.remove("hide");
            modalTitle.innerHTML = title;
        }
        if (text) {
            if (modalText.classList.contains("hide"))
                modalText.classList.remove("hide");
            modalText.innerHTML = text;
        }
        if (button1) {
            if (modalButtons.classList.contains("hide"))
                modalButtons.classList.remove("hide");
            if (modalButton1.classList.contains("hide"))
                modalButton1.classList.remove("hide");
            modalButton1.innerHTML = button1.name;
            modalButton1.onclick = button1.function;
        }

        if (button2) {
            if (modalButtons.classList.contains("hide"))
                modalButtons.classList.remove("hide");
            if (modalButton2.classList.contains("hide"))
                modalButton2.classList.remove("hide");

            modalButton2.innerHTML = button2.name;
            modalButton2.onclick = button2.function;
        }
        document.addEventListener(
            "keydown",
            (escapeModal = function (e) {
                let keyCode = e.keyCode;
                if (keyCode === 27) {
                    //keycode is an Integer, not a String
                    window.modal("close");
                }
            })
        );
    } else {
        document.removeEventListener("keydown", escapeModal);

        modal.classList.add("hide");
        modalButtons.classList.add("hide");
        modalTitle.innerHTML = "";
        modalText.innerHTML = "";
        modalButton1.innerHTML = "";
        modalButton1.onclick = null;
        modalButton2.innerHTML = "";
        modalButton2.onclick = null;

        modalTitle.classList.add("hide");
        modalText.classList.add("hide");
        modalButton1.classList.add("hide");
        modalButton2.classList.add("hide");
    }
};

window.modalFromTask = function (props) {
    // generalinfo: "Холсты 30х40" -
    // info: null -
    // master: 4 -
    // name: "Натяжка холста на подрамник" -
    // start: "2021-03-11 16:40:00"
    // time: 6
    // console.log (props)

    let html = "";
    html += '<form id="detail-task-form" action="/saveedittask" method="POST">';
    html += `<input type="hidden" name="id" value="${props.id}">`;

    html += `<input type="hidden" name="_token" value="${document.querySelector('input[name="_token"]').value}">`
    html += '<div class="form-block">';

    html += '<div class="form-elem">';
    html += '<label for="name">Название задачи</label>';
    html += `<input class="input-required" type="text" name="name" value="${props.name}">`;
    html += "</div>";

    html += '<div class="form-elem">';
    html += '<label for="generalinfo">Основные параметры</label>';
    html += `<input class="input-required" type="text" name="generalinfo" value="${props.generalinfo}">`;
    html += "</div>";

    html += '<div class="form-elem">';
    html += '<label for="info">Дополнительные параметры</label>';
    html += `<input type="text" name="info" value="${props.info}">`;
    html += "</div>";

    html += "</div>";

    html += '<div class="form-block">';

    html += '<div class="form-elem form-elem__min">';
    html += '<label for="master">ID Мастера</label>';
    html += `<input class="input-required" list="masters" name="master" value="${props.master}">`;
    html += "</div>";

    html += '<div class="form-elem form-elem__min">';
    html += '<label for="start">Дата начала задачи</label>';
    html += `<input class="input-required" type="text" name="start" value="${props.start}">`;
    html += "</div>";

    html += '<div class="form-elem form-elem__min">';
    html += '<label for="time">Время, мин.</label>';
    html += `<input class="input-required" type="text" name="time" value="${props.time}">`;
    html += "</div>";

    html += '<div class="form-elem form-elem__min">';
    html += '<label for="bufer">Буфер, мин.</label>';
    html += `<input class="input-required" type="text" name="bufer" value="${props.buffer}">`;
    html += "</div>";

    html += '<div class="form-elem form-elem__min">';


    html += '<label for="status">Статус задачи:</label><select id="status" name="status">'
    let statusArr = [
        { status: 'wait', name: 'Ожидает исполнения' },
        { status: 'pause', name: 'Остановлена' },
        { status: 'finished', name: 'Завершена' }
    ]

    statusArr.forEach(el => {
        html += `<option value="${el.status}" ${el.status === props.status ? 'selected' : ''}>${el.name}</option>`
    })
    html += '</select>'

    html += "</div>";

    html += "</div>";
    html +=
        '<button class="form-modal-button" type="button" onclick="detailTaskFormSubmit()">Сохранить</button>';


    if (typeof props.id !=='undefined') html += '<input class="delete-confirm" type="checkbox" name="deleteconfirm"><labelfor="deleteconfirm">Удалить задачу?</label>';


    html += "</form>";

    modal("open", null, html);
};

window.detailTaskFormSubmit = function () {
    let requaredInputs = document.querySelectorAll(".input-required");
    let alert = 0;
    requaredInputs.forEach((el) => {
        if (el.value === "") {
            el.classList.add("form-alert");
            alert++;
        } else {
            if (el.classList.contains("form-alert"))
                el.classList.remove("form-alert");
        }
    });
    if (alert === 0) document.getElementById('detail-task-form').submit();
};
