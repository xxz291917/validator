#用法
    
    include 'src/validator.php';
    $validator = new validator;
    $validator->rule('username', 'required')
            ->rule('username', array('minlength' => 4))
            ->label('username', '用户名');
    if ($validator->check()) {
        echo 'OK';
    } else {
        var_dump($validator->getError());
        die;
    }
