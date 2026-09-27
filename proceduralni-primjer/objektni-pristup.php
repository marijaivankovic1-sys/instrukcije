<?php

$host = 'localhost';
$korisnik = 'root';
$lozinka = '';
$baza = 'instrukcije_db';

/*
 * OBJEKTNO-ORIJENTIRANI MySQLi PRISTUP
 */
$veza = new mysqli(
    $host,
    $korisnik,
    $lozinka,
    $baza
);

if ($veza->connect_error) {
    die(
        'Povezivanje s bazom nije uspjelo: ' .
        $veza->connect_error
    );
}

$veza->set_charset('utf8mb4');

$sql = "
    SELECT
        k.id,
        k.ime,
        k.prezime,
        k.email,
        k.status,
        GROUP_CONCAT(
            DISTINCT u.naziv
            ORDER BY u.naziv
            SEPARATOR ', '
        ) AS uloge
    FROM korisnici k

    LEFT JOIN korisnik_uloga ku
        ON ku.korisnik_id = k.id

    LEFT JOIN uloge u
        ON u.id = ku.uloga_id

    GROUP BY
        k.id,
        k.ime,
        k.prezime,
        k.email,
        k.status

    ORDER BY k.id
";

$rezultat = $veza->query($sql);

if (!$rezultat) {
    die(
        'Greška pri izvršavanju upita: ' .
        $veza->error
    );
}
?>

<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">

    <title>
        Objektno-orijentirani pristup bazi
    </title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        h1 {
            margin-bottom: 10px;
        }

        p {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <h1>
        Objektno-orijentirani pristup bazi podataka
    </h1>

    <p>
        Podaci iz baze dohvaćeni su
        objektno-orijentiranim MySQLi pristupom.
    </p>

    <table>

        <thead>
            <tr>
                <th>ID</th>
                <th>Ime</th>
                <th>Prezime</th>
                <th>Email</th>
                <th>Uloga</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

            <?php while ($red = $rezultat->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($red['id']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($red['ime']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($red['prezime']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($red['email']) ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $red['uloge'] ??
                            'Nema dodijeljenu ulogu'
                        )
                        ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($red['status']) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </tbody>

    </table>

</body>

</html>

<?php

$rezultat->free();
$veza->close();

?>