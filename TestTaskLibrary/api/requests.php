<?php
include 'connection.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if (isset($input['id'])&!isset($input['login'])&!isset($input['password'])&!isset($input['book_id'])) {
            $id = $input['id'];
            $result = $con->query("SELECT user_id, login FROM users where user_id!='$id'");
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode($users);
        } elseif(isset($input['login'])&isset($input['password'])&!isset($input['id'])&!isset($input['book_id'])) {
            $login = $input['login'];
            $password = $input['password'];
            $result = $con->query("SELECT * FROM users where login = '$login' and password='$password'");
            $userData = $result->fetch_assoc();
            echo json_encode($userData);
        }elseif (isset($input['book_id'])&isset($input['id'])) {
            $book_id = $input['book_id'];
            $id = $input['id'];
            //$result = $con->query("SELECT authors.author_name, books.book_name, books.book_body FROM books JOIN books_and_authors ON books.book_id = books_and_authors.book_id JOIN authors ON authors.author_id = books_and_authors.author_id");
            $result = $con->query("select books.book_id,books.book_name,books.book_body from books join booklist on books.book_id = booklist.book_id join share_tab on booklist.user_id = share_tab.sender_id where sender_id = '$id'");
            $books = [];
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
            echo json_encode($books);
        }elseif (isset($input['id_for_booklist'])) {
            $user_id = $input['id_for_booklist'];
            $result = $con->query("SELECT books.book_id,books.book_name,books.book_body FROM books join booklist on books.book_id = booklist.book_id where booklist.user_id = '$user_id'");
            $books = [];
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
            echo json_encode($books);
        }
        break;

    case 'POST':
        if(isset($input['login'])&isset($input['password'])) {
            $login = $input['login'];
            $password = $input['password'];
            $con->query("INSERT INTO users (login, password) VALUES ('$login', '$password')");
            echo json_encode(["message" => "User registered successfully."]);
            break;
        }
        elseif (isset($input['book_name'])&isset($input['book_body'])&isset($input['author_name'])) {
            $book_name = $input['book_name'];
            $book_body = $input['book_body'];
            $author_name = $input['author_name'];
            $con->begin_transaction();
            try {
                $con->query("INSERT INTO books (book_name, book_body) VALUES ('$book_name', '$book_body')");
                $con->query("INSERT INTO authors (author_name) VALUES ('$author_name')");
                $con->query("INSERT INTO books_and_authors (book_id, author_id) 
SELECT books.book_id, authors.author_id from books JOIN authors WHERE authors.author_name = '$author_name' and books.book_name = '$book_name'");
                $con->commit();
            } catch (mysqli_sql_exception $e) {
                $con->rollback();
                echo json_encode(["message" => "Transaction failed."]);
            }
            echo json_encode(["message" => "Book added successfully."]);
            break;
        }elseif (isset($input['sender_id'])&isset($input['receiver_id'])) {
            $sender_id = $input['sender_id'];
            $receiver_id = $input['receiver_id'];
            $con->query("INSERT INTO share_tab (sender_id, receiver_id) values ('$sender_id', '$receiver_id')");
        }

    case 'PUT':
        if (isset($input['book_id'])&isset($input['book_name'])&isset($input['book_body'])){
            $id = $input['book_id'];
            $book_name = $input['book_name'];
            $book_body = $input['book_body'];
            $con->query("UPDATE books SET book_name = '$book_name', book_body = '$book_body' WHERE book_id = '$id'");
            echo json_encode(["message" => "Book updated successfully"]);
            break;
        }

    case 'DELETE':
        $id = $input['book_id'];
        $con->begin_transaction();
        try {
            $con->query("DELETE FROM books_and_authors WHERE book_id = '$id'");
            $con->query("DELETE FROM books WHERE id=$id");
            $con->commit();
        } catch (mysqli_sql_exception $e) {
            $con->rollback();
            echo json_encode(["message" => "Transaction failed."]);
        }
        echo json_encode(["message" => "Book deleted successfully"]);
        break;

    default:
        echo json_encode(["message" => "Invalid request method"]);
        break;
}

$con->close();
?>