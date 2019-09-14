function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function convert() {
    let f = new FormData();
    f.append('file', document.getElementById('file').files[0]);
    f.append('user_id', getCookie('user_id'));

    let xhr = new XMLHttpRequest();
    xhr.open('POST', '/convert/', true);

    xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
            let percentComplete = (e.loaded / e.total) * 100;
            console.log(percentComplete + '% uploaded');
        }
    };

    xhr.onload = function() {
        if (xhr.status === 200) {
            console.log(this.response)
            var resp = JSON.parse(this.response);
            console.log('Server got:', resp);
        }
    };
    xhr.send(f);
}

