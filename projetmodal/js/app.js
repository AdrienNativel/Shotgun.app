$(document).ready(function () {
    //Sera utile pour répéter une action à intervalles réguliers
    let timer;

    $(window).on('hashchange', route);

    //Fonction utilisée dans le case #attente
    //Permet de savoir s'il y a encore des places
    //Si on est encore dans la file d'attente
    //Notre rang dans la file le cas échéant
    function sendPostRequest(template) {
        $.post(url + "shotgun.php", { "id": localStorage.getItem('identifiant'), "token": localStorage.getItem('token'), "nomsg": localStorage.getItem('nomsg') }, function (response) {
            //S'il n'y a plus de places
            if (response["place"] == "non"){
                const myModal = new bootstrap.Modal(document.getElementById('myModal4'));
                myModal.show();
                window.location.hash = 'accueil';
            }
            //Si on est passé à l'étape suivante
            else if (response["finalisation"] == "oui") {
                window.location.hash = 'finalisation';
            }
            //Si on n'est plus dans la file d'attente
            else if (response["rang"] == -1) {
                window.location.hash = 'accueil';
            }
            //On est encore dans la file d'attente, on veut notre rang
            else {
                $("#position").html(response["rang"][0]["position"]);
            }
        });
    }

    function route() {
        var hash = window.location.hash;
        switch (hash) {
            //Création d'un shotgun
            case "#creation":
                $.get("template/creation.tpl.html", function (template) {
                    $("#my-content").html(template);
                    //Pour le choix de l'heure
                    $(function () {
                        $('#heure').timepicker({
                            showMeridian: false, // Utilise 24 heures
                            minuteStep: 1
                        });
                    });
                    
                    //Pour charger et apercevoir l'image
                    const fileInput = document.getElementById('photo');
                    const imagePreview = document.getElementById('imagePreview');

                    fileInput.addEventListener('change', function (event) {
                        const file = event.target.files[0];
                        if (file) {
                            const reader = new FileReader();

                            reader.onload = function (e) {
                                imagePreview.src = e.target.result;
                                imagePreview.style.display = 'block';
                            }

                            reader.readAsDataURL(file);
                        }
                    });
                    var nomBinet;
                    var nomSg;
                    var nbPlaces;
                    var date;
                    var heure;
                    var description;
                    var image;
                    $("#creer1").click(function () {
                        nomBinet = $("#nomBinet").val();
                        nomSg = $("#nomSg").val();
                        nbPlaces = $("#nbPlaces").val();
                        date = $("#date").val();
                        heure = $("#heure").val();
                        description = $("#description").val();
                        image = $("#imagePreview").attr("src");
                        $.post(url + "creation.php", { "nomBinet": nomBinet, "nomSg": nomSg, "nbPlaces": nbPlaces, "description": description, "date": date, "heure": heure, "image": image }, function (response) {
                            content = Mustache.render(template, response);
                            $("#my-content").html(content);
                            const myModal = new bootstrap.Modal(document.getElementById('myModal'));
                            myModal.show();
                            window.location.hash = 'accueil';
                        });
                    });
                }, "html");
                break;
            //Pour avoir la liste des shotguns
            case "#sg":
                $.get("template/listeShotgun.tpl.html", function (template) {
                    $.getJSON(url + "catalogue.php", function (data) {
                        content = Mustache.render(template, data);
                        $("#my-content").html(content);
                    });
                }, "html");
                break;
            //Accès au shotgun s'il est ouvert
            case "#connexion":
                var codeShotgun = sessionStorage.getItem('codeShotgun');
                $.post(url + "accesSg.php", { "code": codeShotgun }, function (response) {
                    //Si le shotgun est ouvert
                    if (response.ready) {
                        window.location.hash = 'attente';
                        localStorage.setItem('identifiant', response.id.id);
                        localStorage.setItem('token', response.id.token);
                        localStorage.setItem('nomsg', codeShotgun);
                    }
                    //Sinon
                    else {
                        const myModal = new bootstrap.Modal(document.getElementById('myModal3'));
                        myModal.show();
                        window.location.hash = 'accueil';
                    }
                })

                break;
            //Se connecter pour créer un shotgun
            case "#seConnecter":
                $.get("template/connexion.tpl.html", function (template) {
                    $("#my-content").html(template);
                    let bouton = document.getElementById("seConnecter");
                    bouton.addEventListener("click", function () {
                        oAuthConnect()
                            .done(function (data) {
                                localStorage.setItem('access_token', data.access_token);
                                window.location.hash = 'creation';
                            })
                            .fail(function (xhr, status, error) {
                                const myModal = new bootstrap.Modal(document.getElementById('myModal'));
                                myModal.show();
                            });
                    });
                }, "html");
                break;
            //Créer un compte, pour plus tard créer un shotgun
            case "#creercompte":
                $.get("template/creercompte.tpl.html", function (template) {
                    $("#my-content").html(template);
                    var identifiant;
                    var mdp1;
                    var mdp2;
                    var prenom;
                    var nom;
                    var mail;
                    var binet;
                    let bouton2 = document.getElementById("creer2");
                    bouton2.addEventListener("click", function () {
                        identifiant = $("#identifiant").val();
                        mdp1 = $("#mdp1").val();
                        mdp2 = $("#mdp2").val();
                        prenom = $("#prenom").val();
                        nom = $("#nom").val();
                        mail = $("#mail").val();
                        binet = $("#binet").val();
                        console.log({ identifiant, mdp1, mdp2, prenom, nom, mail, binet });
                        $.post(url + "register.php", { "identifiant": identifiant, "mdp1": mdp1, "mdp2": mdp2, "prenom": prenom, "nom": nom, "mail": mail, "binet": binet }, function (response) {
                            const myModal = new bootstrap.Modal(document.getElementById('myModal'));
                            $("#modalBodyContent").html(response.resultat);
                            myModal.show();
                            let bouton = document.getElementById("seConnecter");
                            window.location.hash = 'seConnecter';
                        });
                    }, "html");
                }, "html");
                break;
            //File d'attente du shotgun
            case "#attente":
                $.get("template/attente.tpl.html", function (template) {
                    $("#my-content").html(template);
                    //Récupération de l'image s'il y en a une
                    $.post(url + "image.php", { "nomsg": localStorage.getItem('nomsg') }, function (response) {

                        $('#attente').css('background', 'url(' + JSON.stringify(response.image['image']) + ')');
                        $('#attente').css('background-size', 'cover');
                        $('#attente').css('background-position', 'center');
                        $('#attente').css('background-repeat', 'no-repeat');
                        $('#attente').css('min-height', '100vh');

                    });
                    //Appel à la fonction senPostRequest toutes les 2 secondes
                    const interval = 2000;
                    timer = setInterval(() => sendPostRequest(template), interval);
                }, "html");
                break;
            //Renseigner les données pour finaliser son inscription au shotgun
            case "#finalisation":
                //On arrete l'appel à la fonction sendPostRequest toutes les deux secondes
                clearInterval(timer);
                $.get("template/finalisation.tpl.html", function (template) {
                    $("#my-content").html(template);
                    var prenom;
                    var nom;
                    var trigramme;
                    $("#creer3").click(function () {
                        prenom = $("#prenom2").val();
                        nom = $("#nom2").val();
                        trigramme = $("#trigramme").val();
                        $.post(url + "ajout.php", { "nom": nom, "prenom": prenom, "trigramme": trigramme, "id": localStorage.getItem('identifiant'), "token": localStorage.getItem('token'), "nomsg": localStorage.getItem('nomsg') }, function (response) {
                            content = Mustache.render(template, response);
                            $("#my-content").html(content);
                            const myModal = new bootstrap.Modal(document.getElementById('myModal2'));
                            myModal.show();
                            window.location.hash = 'accueil';
                        });
                    })
                }, "html");
                break;
            default:
                //On arrete l'appel à la fonction sendPostRequest toutes les deux secondes (dans le cas où on a été redirigé vers l'accueil)
                clearInterval(timer);
                $.get("template/accueil.tpl.html", function (template) {
                    $("#my-content").html(template);
                }, "html");
                break;
        }
    }

    route();
});