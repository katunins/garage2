window.ajax = function(url, data, callBack = null) {
    fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json, text-plain, */*',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
            },
            method: 'post',
            credentials: 'same-origin',
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(response => {
            if (callBack) callBack(response);
            // console.log (callBack);
        })
        .catch(function(error) {
            console.log(error);
        });
};

window.modal = function(
    action,
    title = null,
    text = null,
    button1 = null,
    button2 = null
) {
    let modal = document.getElementById('modal');

    let modalTitle = modal.querySelector('.modal-title');
    let modalText = modal.querySelector('.modal-text');
    let modalButtons = modal.querySelector('.modal-buttons');
    let modalButton1 = modal.querySelector('.modal-button1');
    let modalButton2 = modal.querySelector('.modal-button2');

    if (action == 'open') {
        if (modal.classList.contains('hide')) modal.classList.remove('hide');
        if (title) {
            if (modalTitle.classList.contains('hide'))
                modalTitle.classList.remove('hide');
            modalTitle.innerHTML = title;
        }
        if (text) {
            if (modalText.classList.contains('hide'))
                modalText.classList.remove('hide');
            modalText.innerHTML = text;
        }
        if (button1) {
            if (modalButtons.classList.contains('hide'))
                modalButtons.classList.remove('hide');
            if (modalButton1.classList.contains('hide'))
                modalButton1.classList.remove('hide');
            modalButton1.innerHTML = button1.name;
            modalButton1.onclick = button1.function;
        }

        if (button2) {
            if (modalButtons.classList.contains('hide'))
                modalButtons.classList.remove('hide');
            if (modalButton2.classList.contains('hide'))
                modalButton2.classList.remove('hide');

            modalButton2.innerHTML = button2.name;
            modalButton2.onclick = button2.function;
        }
        document.addEventListener('keydown', escapeModal = function(e) {
            let keyCode = e.keyCode;
            if (keyCode === 27) {
                //keycode is an Integer, not a String
                window.modal('close');
            }
        });
    } else {
        document.removeEventListener('keydown', escapeModal);

        modal.classList.add('hide');
        modalButtons.classList.add('hide');
        modalTitle.innerHTML = '';
        modalText.innerHTML = '';
        modalButton1.innerHTML = '';
        modalButton1.onclick = null;
        modalButton2.innerHTML = '';
        modalButton2.onclick = null;

        modalTitle.classList.add('hide');
        modalText.classList.add('hide');
        modalButton1.classList.add('hide');
        modalButton2.classList.add('hide');
    }
};