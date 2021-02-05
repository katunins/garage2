window.ajax = function(
    url,
    data,
    callBack = null
) {
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