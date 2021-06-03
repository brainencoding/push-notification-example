<?php

class UsersController {
  public function getAll()
  {
      return json_decode(file_get_contents("./user.json"),  true);
  }

  public function setToJson($arr) 
  {
      $arr = json_encode($arr);
      file_put_contents("./user.json", $arr);
  }

  public function checkExist($user)
    {
    $allUsers = $this->getAll();
    $isExists = false;

    foreach($allUsers as $_user)
    {
        if ($_user[0]['authToken'] == $user['authToken'])
         { 
            $isExists = true;
            break;
        }
    }

    return $isExists;
  }

  public function updateUser($user) 
  {
    $isExists = $this->checkExist($user);

    if ($isExists) 
    {
        $allUsers = $this->getAll();

        foreach($allUsers as $_user) 
        {
            if ($_user[0]['authToken'] == $user['authToken']) { 
                $_user[0]['endpoint'] = $user['endpoint'];
                break;
            }
        }
    }
  }

  public function removeUser($authToken) 
  {
    $allUsers = $this->getAll();
    $idx = 0;
    $neededIndex = false;

    foreach($allUsers['users'] as $user)
    {
        if ($user['authToken'] == $authToken['authToken'])
        {
             $neededIndex = $idx;
        }

        $idx++;
    }
    
    if ($neededIndex) 
    { 
        unset($allUsers[$idx]);
        return $allUsers;
    }
  }

  public function subscribe($newUser)
  {
      $allUsers = $this->getAll();
      $isExists = $this->checkExist($newUser);

        if ($isExists)
        {
            $allUsers = $this->removeUser($newUser['authToken']);
        }

      array_push($allUsers['users'], $newUser);

      $this->setToJson($allUsers);
  }
}

$newSubscriber = json_decode(file_get_contents('php://input'), true);

$usersController = new UsersController;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) 
{
    case 'POST':
        $usersController->subscribe($newSubscriber);
        break;
    case 'PUT':
        $usersController->updateUser($newSubscriber);
        break;
    case 'DELETE':
        $usersController->removeUser($newSubscriber);
        break;
    default:
        echo "Error: method not handled";
        return;
}


