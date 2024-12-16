
var type="creation";
var client_id = "testclient";
var client_secret = "testpass";

function oAuthConnect() {
    var username = $("#identifiant").val();
    var password = $("#motdp").val();
    return $.post(url + "token.php", {
        type: type,
        client_id: client_id,
        client_secret: client_secret,
        grant_type: "password",
        username: username,
        password: password,
    });
}