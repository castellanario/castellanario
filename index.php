<?php

include './config.php';

/* Database schema

CREATE TABLE `castellanario` (
  `id` bigint UNSIGNED NOT NULL,
  `term` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `term_slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `region` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `region_slug` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `upvotes` bigint UNSIGNED NOT NULL DEFAULT '0',
  `downvotes` bigint UNSIGNED NOT NULL DEFAULT '0',
  `flags` int UNSIGNED NOT NULL DEFAULT '5',
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `example` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `castellanario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `term` (`term`),
  ADD KEY `term_slug` (`term_slug`),
  ADD KEY `region` (`region`),
  ADD KEY `region_slug` (`region_slug`),
  ADD KEY `upvotes` (`upvotes`),
  ADD KEY `downvotes` (`downvotes`),
  ADD KEY `posted` (`posted`);

ALTER TABLE `castellanario`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

*/

/* Connect to database */

$db_connection = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);

if ($db_connection->connect_error) {
    die('Connection failed: ' . $db_connection->connect_error);
}

$db_connection->select_db(DB_NAME);

// Set charset
$db_connection->set_charset('utf8mb4');

/* Perform POST actions
- Should post a new term? (validate and do or error)
- Should vote? (validate and do or error)
*/

// Are we trying to add a term?
if (isset($_POST['add-term']) and (strlen($_POST['term']) > 2) and (strlen($_POST['region']) > 2) and (strlen($_POST['term']) < 101) and (strlen($_POST['region']) < 41) and (strlen(normalize_whitespace($_POST['explanation'])) > 25) and (strlen(normalize_whitespace($_POST['example'])) > 25) and (!empty($_POST['g-recaptcha-response']))) {

    $recaptcha_is_valid = verify_recaptcha($_POST["g-recaptcha-response"]);

    $term = $db_connection->real_escape_string(strip_tags(normalize_whitespace($_POST['term'])));
    $term_slug = slugify($_POST['term']);
    $region = $db_connection->real_escape_string(strip_tags(normalize_whitespace($_POST['region'])));
    $region_slug = slugify($_POST['region']);
    $explanation = $db_connection->real_escape_string(strip_tags(normalize_whitespace($_POST['explanation'])));
    $example = $db_connection->real_escape_string(strip_tags(normalize_whitespace($_POST['example'])));

    if ($recaptcha_is_valid) {
        if (!$db_connection->query("INSERT INTO `castellanario` (`term`, `term_slug`, `region`, `region_slug`, `explanation`, `example`) VALUES ('" . $term . "', '" . $term_slug . "', '" . $region . "', '" . $region_slug . "', '" . $explanation . "', '" . $example . "')")) {
            echo("Error description: " . $db_connection->error);
            exit;
        }
        $headers = "From: info@castellanario.com\r\n";
        $headers.= "MIME-Version: 1.0\r\n";
        $headers.= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers.= "X-Priority: 1\r\n";
        $subject = 'Nuevo término: ' . $term;
        $message = $region . '<br>' . $explanation . '<br>' . $example;
        mail('castellanario@gmail.com', $subject, $message, $headers);
        header('Location: /' . $term_slug . '/mas-recientes');
        exit;
    }
}

/* Determine current order clause */
$order_by = 'RAND()';
if (isset($_GET['order'])) {
    switch ($_GET['order']) {
        case 'mas-recientes':
            $order_by = '`id` DESC';
            break;
        case 'mas-antiguos':
            $order_by = '`id` ASC';
            break;
        case 'mejor-votados':
            $order_by = '`upvotes` DESC';
            break;
        case 'peor-votados':
            $order_by = '`downvotes` DESC';
            break;
        default:
            redirect_to_home();
    }
}

/* Determine current action */
$action = 'show-random';
$html_title = 'Castellanario | Diccionario de Castellano';
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add-term':
            $action = 'add-term';
            $html_title = 'Añadir palabra o expresión | Castellanario';
            break;
        case 'show-single-term':
            $action = 'show-single-term';
            if (isset($_GET['term-slug'])) {
                $term_slug = $db_connection->real_escape_string($_GET['term-slug']);
                $returned_terms = $db_connection->query("SELECT * FROM `castellanario` WHERE `term_slug` = '" . $term_slug . "' ORDER BY " . $order_by . " LIMIT 150");
                if ($returned_terms->num_rows === 0) {
                    redirect_to_home();
                }
                $terms_data = array();
                while ($term_data = $returned_terms->fetch_assoc()) {
                    $terms_data[] = $term_data;
                }
                $html_title = 'Significado de ' . $terms_data[0]['term'] . ' | Castellanario';
            } else {
                redirect_to_home();
            }
            break;
        case 'show-region-terms':
            $action = 'show-region-terms';
            if (isset($_GET['region-slug'])) {
                $region_slug = $db_connection->real_escape_string($_GET['region-slug']);
                $returned_terms = $db_connection->query("SELECT * FROM `castellanario` WHERE `region_slug` = '" . $region_slug . "' ORDER BY " . $order_by . " LIMIT 150");
                if ($returned_terms->num_rows === 0) {
                    redirect_to_home();
                }
                $terms_data = array();
                while ($term_data = $returned_terms->fetch_assoc()) {
                    $terms_data[] = $term_data;
                }
                $html_title = 'Palabras y expresiones de ' . $terms_data[0]['region'] . ' | Castellanario';
            } else {
                redirect_to_home();
            }
            break;
        case 'privacy':
            $action = 'privacy';
            break;
        default:
            redirect_to_home();
    }
}
if ($action === 'show-random') {
    $returned_terms = $db_connection->query("SELECT * FROM `castellanario` ORDER BY RAND() LIMIT 100");
    $terms_data = array();
    while ($term_data = $returned_terms->fetch_assoc()) {
        $terms_data[] = $term_data;
    }
}

/* Print HTML header with very simple CSS styles */
?>

    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $html_title; ?></title>
        <style>
            @font-face {
                font-family: 'Dosis';
                src: url('/assets/Dosis-Regular.ttf');
            }

            @font-face {
                font-family: 'SpecialElite';
                src: url('/assets/SpecialElite-Regular.ttf');
            }

            body {
                font-family: Dosis, sans-serif;
                padding: 3rem;
                font-size: 17px;
                color: #111;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            a {
                color: #111;
                text-decoration: none;
            }

            header {
                display: flex;
                flex-direction: column;
                margin-bottom: 4rem;
                align-items: center;
                gap: 2rem;
            }

            ul {
                list-style: none;
                max-width: 500px;
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
                gap: 6rem;
            }

            li {
                display: flex;
                flex-direction: column;
                line-height: 1.4;
                gap: 2rem;
            }

            h1 {
                font-family: SpecialElite, sans-serif;
            }

            h1 span{
                color: #999;
            }

            h2 {
                font-family: SpecialElite, sans-serif;
                font-size: 25px;
            }

            form {
                display: flex;
                max-width: 500px;
                flex-direction: column;
                gap: 2rem;
            }

            label, input, textarea {
                display: block;
                width: 100%;
            }

            label {
                margin-bottom: 1rem;
            }

            input, textarea {
                padding: 1rem;
            }

            textarea {
                height: 10rem;
                resize: vertical;
            }

            .tag {
                display: inline-block;
                background-color: #222;
                color: #FFF;
                padding: 0.5rem 1.2rem;
                border-radius: 1rem;
                font-size: 14px;
                letter-spacing: 0.1rem;
            }

            .tag:hover{
                background-color: #111;
            }

            .privacy {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                max-width: 500px;
            }

            footer {
                margin-top: 4rem;
                text-align: center;
            }

            @media (min-width: 800px){
                body {
                    padding: 4rem;
                    font-size: 18px;
                }
                header {
                    flex-direction: row;
                    justify-content: space-between;
                    margin-bottom: 6rem;
                }
                h2 {
                    font-family: SpecialElite, sans-serif;
                    font-size: 40px;
                }
            }

            @media (min-width: 1100px){
                body {
                    padding: 6rem;
                    font-size: 20px;
                }
                header {
                    margin-bottom: 10rem;
                }
                h2 {
                    font-family: SpecialElite, sans-serif;
                    font-size: 60px;
                }
            }
        </style>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-J2JFKPHKCZ"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-J2JFKPHKCZ');
        </script>
    </head>
    <body>

    <header>
        <h1><a href="/">Castellanario<span>.com</span></a></h1>
        <?php
        if ($action === 'add-term') {
            echo '<a class="tag" href="/">Cancelar</a>';
        } else {
            echo '<a class="tag" href="/agregar">Añadir palabra o expresión</a>';
        }
        ?>
    </header>

    <?php
    /* Should show any form?
    - Show the "add term" form (with errors if any) and Captcha
     */
    switch ($action) {
        case 'add-term':
            ?>

            <form action="/agregar" method="post">

                <h2>Comparte lo que sabes!</h2>

                <div>
                    <label for="term">Palabra o expresión</label>
                    <input minlength="3" maxlength="100" required type="text" name="term" id="term"
                           placeholder="Ej: Empanao, Me vale verga...">
                </div>

                <div>
                    <label for="region">Región</label>
                    <input minlength="3" maxlength="40" required type="text" name="region" id="region"
                           placeholder="Ej: Andalucía, Chile, Buenos Aires...">
                </div>

                <div>
                    <label for="explanation">Explicación</label>
                    <textarea required minlength="40" name="explanation" id="explanation"
                              placeholder="Ej: Dícese de la persona que no sabe ni donde tiene la cara."></textarea>
                </div>

                <div>
                    <label for="example">Ejemplo</label>
                    <textarea required minlength="40" name="example" id="example"
                              placeholder="Ej: Kiyo Paco, no veas si estás empanao hoy colega...!"></textarea>
                </div>

                <div
                        class="g-recaptcha"
                        data-sitekey="<?php echo RECAPTCHA_PUBLIC_KEY; ?>">
                </div>

                <div>
                    <input type="submit" name="add-term" value="Enviar">
                </div>
            </form>
            <?php
            break;
        case 'show-random':
        case 'show-single-term':
        case 'show-region-terms':
            echo '<ul>';
            foreach ($terms_data as $term_data) {
                if ($action !== 'show-single-term') {
                    $title_html = '<a href="/' . $term_data['term_slug'] . '">' . $term_data['term'] . '</a>';
                } else {
                    $title_html = $term_data['term'];
                }
                $region_html = '';
                if ($action !== 'show-region-terms') {
                    $region_html = '<p><a class="tag" href="/region/' . $term_data['region_slug'] . '">' . $term_data['region'] . '</a></p>';
                }
                echo '
            <li>
                <h2>' . $title_html . '</h2>
                <p>' . $term_data['explanation'] . '</p>
                <p>' . $term_data['example'] . '</p>
                ' . $region_html . '
            </li>
            ';
            }
            echo '</ul>';
            break;
        case 'privacy':
            ?>
            <div class="privacy">
                <h2>Política de privacidad</h2>
                <p>Aquí puedes enterarte de todo.</p>
            </div>
        <?php
    }


    /* Print HTML footer */

    ?>
    <footer>
        <a href="/privacidad">Privacidad</a> - <a href="https://www.instagram.com/castellanario">Insta</a> - <a
                href="https://twitter.com/castellanario">Twitter</a>
    </footer>
    </body>
    </html>

<?php

function verify_recaptcha($token)
{
    $recaptcha_endpoint = "https://www.google.com/recaptcha/api/siteverify";
    $recaptcha_data = [
        "secret" => RECAPTCHA_SECRET_KEY,
        "response" => $token,
    ];
    $request_options = array(
        "http" => array(
            "header" => "Content-type: application/x-www-form-urlencoded\r\n",
            "method" => "POST",
            "content" => http_build_query($recaptcha_data), # Agregar el contenido definido antes
        ),
    );
    $contexto = stream_context_create($request_options);
    $request_result = file_get_contents($recaptcha_endpoint, false, $contexto);
    if ($request_result === false) {
        return false;
    }
    $request_result = json_decode($request_result);
    return $request_result->success;
}

function slugify($text)
{
    $replacements = [
        '<' => '', '>' => '', '-' => ' ', '&' => '', '"' => '', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae', 'Ç' => 'C', "'" => '', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ę' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'L', 'Ľ' => 'L', 'Ĺ' => 'L', 'Ļ' => 'L', 'Ŀ' => 'L', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O', 'Œ' => 'OE', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S', 'Ş' => 'S', 'Ŝ' => 'S', 'Ș' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T', 'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ū' => 'U', 'Ü' => 'Ue', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ś' => 's', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ū' => 'u', 'ů' => 'u', 'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'α' => 'a', 'ß' => 'ss', 'ẞ' => 'b', 'ſ' => 'ss', 'ый' => 'iy', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', '.' => '-', '€' => '-eur-', '$' => '-usd-'
    ];
    // Replace non-ascii characters
    $text = strtr($text, $replacements);
    // Replace non letter or digits with "-"
    $text = preg_replace('~[^\pL\d.]+~u', '-', $text);
    // Replace unwanted characters with "-"
    $text = preg_replace('~[^-\w.]+~', '-', $text);
    // Trim "-"
    $text = trim($text, '-');
    // Remove duplicate "-"
    $text = preg_replace('~-+~', '-', $text);
    // Convert to lowercase
    return strtolower($text);
}

function redirect_to_home()
{
    header('Location: /');
    exit;
}

function normalize_whitespace($string)
{
    $string = preg_replace('/\s+/', ' ', $string);
    return preg_replace('/(\r\n|\r|\n)+/', '', $string);
}
