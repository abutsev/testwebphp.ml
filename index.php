<?php

class Notes {

    private $pdo;

    const dbFile = 'db.sqlite';

    function __construct() {
        $this->pdo = new PDO('sqlite:'.self::dbFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS notes (
        ID      INTEGER PRIMARY KEY AUTOINCREMENT,
        title   TEXT NOT NULL,
        content TEXT NOT NULL,
		tegs 	TEXT NOT NULL,
        created DATETIME NOT NULL
        );');
    }

    public function fetchNotes($id = null) {
        if ($id != null) {
            $stmt = $this->pdo->prepare('SELECT title, content, tegs FROM notes WHERE id = :ID');
            $stmt->bindParam(':ID', $id);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row) {
                $title = $row['title'];
				$tegs =  $row['tegs'];
                header("Content-type: text/plain; charset=utf-8");
                header("Content-Disposition: attachment; filename=$title.txt");
                echo $row['content'];
				echo "\nтеги \n";
				echo $row['tegs'];
                return;
            }
        } else {
            $stmt = $this->pdo->query('SELECT * FROM notes ORDER BY created DESC');
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
    }

    public function create($title, $content, $tegs) {
        $datetime = date("Y-m-d H:i:s");
        $stmt = $this->pdo->prepare('INSERT INTO notes (title, content, tegs, created) VALUES (:title, :content, :tegs, :created)');
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
		$stmt->bindParam(':tegs', $tegs);
        $stmt->bindParam(':created', $datetime);
        $stmt->execute();
    }

    public function delete($id) {
        if ($id == 'all') {
            $stmt = $this->pdo->query('DELETE FROM notes; VACUUM');
        } else {
            $stmt = $this->pdo->prepare('DELETE FROM notes WHERE id = :ID');
            $stmt->bindParam(':ID', $id);
            $stmt->execute();
        }
    }

    public function edit($id, $title, $content, $tegs) {
        $stmt = $this->pdo->prepare('UPDATE notes SET title = :title, content = :content, tegs = :tegs WHERE id = :ID');
        $stmt->bindParam(':ID', $id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
		$stmt->bindParam(':tegs', $tegs);
        $stmt->execute();
    }
}

$notes = new Notes;

if (isset($_POST['new'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
	$tegs = $_POST['tegs'];
    $notes->create($title, $content, $tegs);
    header('Location: .');
    exit();
}
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
	$tegs = $_POST['tegs'];
    $notes->edit($id, $title, $content, $tegs);
    header('Location: .');
    exit();
}
if (!empty($_GET['del'])) {
    $id = $_GET['del'];
    $notes->delete($id);
    header('Location: .');
    exit();
}
if (!empty($_GET['dl'])) {
    $id = $_GET['dl'];
    $notes->fetchNotes($id);
    exit();
}

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title> Простой ежедневник </title>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css">
    <style>
        .container {
            max-width: 680px;
        }

        textarea {
            resize: vertical;    /* allow only vertical stretch */
        }
    </style>

</head>

<body>

    <div class="container">

        <div class="page-header">
            <h2> Напишите задачу </h2>
        </div>

        <form role="form" action="index.php" method="POST">
            <div class="form-group">
                <input class="form-control" type="text" placeholder="Заголовок" name="title" required>
            </div>
            <div class="form-group">
                <textarea class="form-control" rows="5" placeholder="Напишите описание задачи" name="content" autofocus required></textarea>
            </div>
			<div class="form-group">
                <textarea class="form-control" rows="1" placeholder="Напишите теги" name="tegs" autofocus required></textarea>
            </div>
            <div class="btn-group pull-right">
                <button class="btn btn-danger" type="reset"><span class="glyphicon glyphicon-remove"></span> Очистить </button>
                <button class="btn btn-success" name="new" type="submit"><span class="glyphicon glyphicon-send"></span> Сохранить </button>
            </div>
        </form>
    </div>

    <?php
    if (!empty($notes->fetchNotes())):
        $notes = $notes->fetchNotes();
    ?>

    <div class="container" id="notes">
        <div class="page-header">
            <h2> Предыдущие задачи </h2>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Время</th>
                            <th>Дата</th>
                            <th class="pull-right">Кнопка<br></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
<?php foreach ($notes as $row): ?>
                            <td>
                                <small><?= htmlspecialchars(substr($row['title'], 0, 15), ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= date('H:i', strtotime($row['created'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['created'])) ?></td>
                            <td class="pull-right">
                                <div class="btn-group">
                                    <a class="btn btn-default btn-xs" title="Edit this note" href="#" data-toggle="modal" data-target="#<?= $row['ID'] ?>"><span class="glyphicon glyphicon-edit"></span></a>
                                    <a class="btn btn-danger btn-xs" title="Удалить задачу" onclick="deleteNote(<?= $row['ID'] ?>)"><span class="glyphicon glyphicon-trash"></span></a>
                                    <a class="btn btn-info btn-xs" title="Скачать" href="?dl=<?= $row['ID'] ?>" target="_blank"><span class="glyphicon glyphicon-download-alt"></span></a>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="<?= $row['ID'] ?>" role="dialog">
                            <div class="modal-dialog modal-lg">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                                  <h4 class="modal-title">Редактировать задачу</h4>
                                </div>
                                <div class="modal-body">
                                  <form role="form" action="index.php" method="POST">
                                    <div class="form-group">
                                        <input class="form-control" type="text" placeholder="Title" name="title" value="<?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="form-group">
                                        <textarea class="form-control" rows="5" placeholder="What do you have in mind ?" name="content" required><?= htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
									<div class="form-group">
                                        <textarea class="form-control" rows="1" placeholder="Какие теги вы хотите написать?" name="tegs" required><?= htmlspecialchars($row['tegs'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <input type="hidden" name="id" value="<?= $row['ID'] ?>">
                                    <div class="btn-group pull-right">
                                        <button class="btn btn-success" name="edit" type="submit"><span class="glyphicon glyphicon-floppy-disk"></span> Сохранить </button>
                                    </div>
                                </div>
                                </form>
                              </div>
                            </div>
                        </div>
<?php endforeach; ?>
                    </tbody>
            </table>
        </div>
<?php endif; ?>
    </div>

    <script type="text/javascript">
        function deleteNote(id){
            if (confirm('Вы дествительно хотите удалить задачу?')){
                window.location = '?del='+id;
            }
        }
    </script>

</body>

</html>
