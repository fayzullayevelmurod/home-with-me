<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
	

$pdo = new PDO('pgsql:host=5.159.110.251;port=5432;dbname=tme_db', 'postgres', 'admin');

$errors = [];

$requiredFields = ['FormFIO', 'FormBirthDate', 'FormSex', 'FormPassNo', 'FormPassWho', 'FormPassDate', 'FormPassOfficeCode', 'FormCity', 'FormVUNo', 'FormVUCategory', 'FormVUDate', 'FormAddress', 'FormPhone', 'FormCarName', 'FormCarModel', 'FormCarColor', 'FormCarPlate', 'FormCarYear', 'FormRefCode'];
foreach ($requiredFields as $field) {
  if (!isset($_POST[$field])) {
    $errors[] = "Missing field: $field";
  }
}


if (empty($errors)) {
    
  	$driver_license_type = '';
	if (isset($_POST['FormVUCategory']) && is_array($_POST['FormVUCategory'])) {
	    // Проверяем, есть ли несколько выбранных значений
	    if (count($_POST['FormVUCategory']) > 1) {
	        // Если выбрано больше одного значения, объединяем их в строку через запятую
	        $driver_license_type = implode(', ', $_POST['FormVUCategory']);
	    } else {
	        // Если выбрано только одно значение, используем его как строку
	        $driver_license_type = $_POST['FormVUCategory'][0];
	    }
	} elseif (isset($_POST['FormVUCategory'])) {
	    // Если выбрано только одно значение и оно не массив, используем его как строку
	    $driver_license_type = $_POST['FormVUCategory'];
	}
	
	
	$birthday_unformatted = $_POST['FormBirthDate'];
		
	
	if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $birthday_unformatted)) {
    $birthday = $birthday_unformatted;
	} else if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $birthday_unformatted)) {
	    $birthday_change = DateTime::createFromFormat('d.m.Y', $birthday_unformatted);
	    if ($birthday_change) {
	        $birthday = $birthday_change->format('Y-m-d');
	    }
	} else {
	    // Обработка ситуации, когда формат не соответствует ожидаемым
	    // Например, можно установить значение по умолчанию или вывести сообщение об ошибке
	}
	
	
	
	
	
	$driver_license_expire_date_unformated = $_POST['FormBirthDate'];
		
	
	if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $driver_license_expire_date_unformated)) {
    $driver_license_expire_date = $driver_license_expire_date_unformated;
	} else if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $driver_license_expire_date_unformated)) {
	    $driver_license_expire_date_change = DateTime::createFromFormat('d.m.Y', $driver_license_expire_date_unformated);
	    if ($driver_license_expire_date_change) {
	        $driver_license_expire_date = $driver_license_expire_date_change->format('Y-m-d');
	    }
	} else {
	    // Обработка ситуации, когда формат не соответствует ожидаемым
	    // Например, можно установить значение по умолчанию или вывести сообщение об ошибке
	}
 
	
	
	
	
	
	
	$current_time = (new DateTime())->format('Y-m-d H:i:s');

	$name = $_POST['FormFIO'];
	$gender = $_POST['FormSex'];
	$city = $_POST['FormCity'];
	$driver_license = $_POST['FormVUNo'];	
	$address = $_POST['FormAddress'];
	$phone_unformated = $_POST['FormPhone'];
	$mark = $_POST['FormCarName'];
	$model = $_POST['FormCarModel'];
	$color = $_POST['FormCarColor'];
	$gosnumber = $_POST['FormCarPlate'];
	$production_year = $_POST['FormCarYear'];
	$referral_code = $_POST['FormRefCode']; 
	
	$phone = preg_replace("/[^0-9]/", "", $phone_unformated);
	
	// Получаем данные о паспорте из POST запроса
	$passport_series_number = $_POST['FormPassNo'];
	$issued_by = $_POST['FormPassWho'];
	$issue_date = $_POST['FormPassDate'];
	$department_code = $_POST['FormPassOfficeCode'];
	
	// Разделяем серию и номер паспорта
	$passport_series = substr($passport_series_number, 0, 4); // Получаем первые 4 символа (серию паспорта)
	$passport_number = substr($passport_series_number, 5); // Получаем символы начиная с пятого (номер паспорта)
	
	// Формируем информацию о паспорте
	$passport_info = "Паспорт: серия {$passport_series} номер {$passport_number}, Выдан: {$issued_by}, Дата выдачи: {$issue_date}, Код подразделения: {$department_code}";


	$token = strtoupper(bin2hex(random_bytes(16)));
	
	$user_id = 0;
	
	if(!empty($referral_code)){
		
	// Поиск пользователя по реферальному коду
	$find_user_query = "SELECT id FROM drivers WHERE referral_code = ?";
	$stmt_find_user = $pdo->prepare($find_user_query);
	$stmt_find_user->execute([$referral_code]);
	$user_id = $stmt_find_user->fetchColumn(); // Получаем id пользователя, если найден
	
	if ($user_id === false) {
	    $user_id = 0;
	}
		
	} else {
	
	   $user_id = 0;
		
	}
	
	
		
	$create_user = $pdo->prepare("INSERT INTO driver_candidates (name, birthday, gender, passport, city, driver_license, driver_license_type, driver_license_expire_date, address, phone, mark, model, color, gosnumber, production_year, invited_by_driver_id, auth_token, create_time, state) VALUES (:name, :birthday, :gender, :passport, :city, :driver_license, :driver_license_type, :driver_license_expire_date, :address, :phone, :mark, :model, :color, :gosnumber, :production_year, :invited_by_driver_id, :auth_token, :create_time, :state)");

	$create_user->execute([
	    'name' => $name,
	    'birthday' => $birthday,
	    'gender' => $gender,
	    'passport' => $passport_info,
	    'city' => $city,
	    'driver_license' => $driver_license,
	    'driver_license_type' => $driver_license_type,
	    'driver_license_expire_date' => $driver_license_expire_date,
	    'address' => $address,
	    'phone' => $phone,
	    'mark' => $mark,
	    'model' => $model,
	    'color' => $color,
	    'gosnumber' => $gosnumber,
	    'production_year' => $production_year,
	    'invited_by_driver_id' => $user_id,
	    'auth_token' => $token,
	    'create_time' => $current_time,
	    'state' => 0
	  ]);	
	
	// Проверка успешности выполнения запроса
	if ($create_user->rowCount() > 0) {
	    
	    echo json_encode(array('status' => 'success', 'message' => 'Ваша анкета получена и будет рассмотрена в течение 3-х рабочих дней. По результатам проверки, Вам поступит СМС сообщение с данными авторизации'));
	    
	} else {
		
		echo json_encode(array('status' => 'error', 'message' => 'Произошла ошибка при отправке формы.'));
	    
	}
	

  
  
} 


?>
