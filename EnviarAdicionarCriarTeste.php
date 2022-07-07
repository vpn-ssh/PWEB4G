<?php
include("conexao.php");
include_once("functions.php");
if(ProtegePag() == true){
global $banco;

if( ($_SESSION['acesso'] == 1) || ($_SESSION['acesso'] == 2)){

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 
$EditarUsuario = (isset($_POST['EditarUsuario'])) ? trim($_POST['EditarUsuario']) : '';
$EditarSenha = (isset($_POST['EditarSenha'])) ? $_POST['EditarSenha'] : '';
$EditarNome = (isset($_POST['EditarNome'])) ? $_POST['EditarNome'] : '';
$EditarOperadora = (isset($_POST['EditarOperadora'])) ? $_POST['EditarOperadora'] : '';
$CadUser = $_SESSION['id'];
$Data30Dias = time() + (86400);

$SQLUser = "SELECT login FROM login WHERE login = :login";
$SQLUser = $banco->prepare($SQLUser);
$SQLUser->bindParam(':login', $EditarUsuario, PDO::PARAM_STR);
$SQLUser->execute();
$TotalResul = count($SQLUser->fetchAll());

$CadUser = $_SESSION['id'];
$VerificarLimiteTeste = VerificarLimiteTeste($CadUser);
$VerificarCotaTeste = VerificarCotaTeste($CadUser);
$CotaTesteDisponivel = $VerificarLimiteTeste - $VerificarCotaTeste;

	if( ($CotaTesteDisponivel < 1) && ($VerificarLimiteTeste != 0) ){
		echo MensagemAlerta('Erro', 'Você não tem mais limite de teste disponível para hoje!', "danger");
	}
	elseif(empty($CadUser)){
		echo MensagemAlerta('Erro', 'Como você fez isso?', "danger");
	}
	elseif(empty($EditarUsuario)){
		echo MensagemAlerta('Erro', 'Usuário é um campo obrigatório!', "danger");
	}
	elseif( substr_count($EditarUsuario, " ") > 0 ) {
		echo MensagemAlerta('Erro', 'Usuário não pode conter espaço.', "danger");
	}
	elseif(preg_match ('/[^a-zA-Z0-9]/i', $EditarUsuario)) {
		echo MensagemAlerta('Erro', 'Usuário não pode conter caracteres especiais.', 'danger');
	}
	elseif(empty($EditarOperadora)){
		echo MensagemAlerta('Erro', 'Operadora é um campo obrigatório!', "danger");
	}
	elseif(count($EditarOperadora) > 1){
		echo MensagemAlerta('Erro', 'Permitido selecionar apenas uma operadora!', "danger");
	}
	elseif($TotalResul > 0){
		echo MensagemAlerta('Erro', 'Usuário já encontra-se em uso!', "danger");
	}
	elseif(empty($EditarSenha)){
		echo MensagemAlerta('Erro', 'Senha é um campo obrigatório!', "danger");
	}
	elseif(empty($EditarNome)){
		echo MensagemAlerta('Erro', 'Nome é um campo obrigatório!', "danger");
	}
	else{
		
	$Operadora = implode($EditarOperadora);
	
	//Seleciona o servidor
	$SQLServer = "SELECT * FROM servidor WHERE nome = :nome";
	$SQLServer = $banco->prepare($SQLServer);
	$SQLServer->bindParam(':nome', $Operadora, PDO::PARAM_STR);
	$SQLServer->execute();
	$LnServer = $SQLServer->fetch();
				
	$DataExpirar = date('Y-m-d', $Data30Dias);
	$connection = ssh2_connect($LnServer['server'], $LnServer['porta']);
	ssh2_auth_password($connection, $LnServer['user'], $LnServer['senha']);
	$stream = ssh2_exec($connection, 'sudo useradd -e '.$DataExpirar.' -M -s /bin/false '.trim($EditarUsuario).'');
	stream_set_blocking($stream, true);
	stream_set_timeout($stream, 15);
	fclose($stream);
	
	$AlterarSenha = ssh2_exec($connection, 'sudo usermod -p $(openssl passwd -1 '.escapeshellarg(trim($EditarSenha)).') '.trim($EditarUsuario).'');
	stream_set_blocking($AlterarSenha, true);
	stream_set_timeout($AlterarSenha, 15);
	fclose($AlterarSenha);
	
	$DataAtual = date('Y-m-d');
	$DataAtual = strtotime($DataAtual);
	
	$acesso = 4;
	$SQL = "INSERT INTO login (
			id_cad,
			nome,
            login,
            senha,
			expiredate,
			operadora,
			acesso,
			data
            ) VALUES (
			:id_cad,
			:nome,
            :login,
            :senha,
			:expiredate,
			:operadora,
			:acesso,
			:data
			)";
	$SQL = $banco->prepare($SQL);
	$SQL->bindParam(':id_cad', $CadUser, PDO::PARAM_INT); 
	$SQL->bindParam(':nome', $EditarNome, PDO::PARAM_STR); 
	$SQL->bindParam(':login', $EditarUsuario, PDO::PARAM_STR); 
	$SQL->bindParam(':senha', $EditarSenha, PDO::PARAM_STR); 
	$SQL->bindParam(':expiredate', $Data30Dias, PDO::PARAM_STR); 
	$SQL->bindParam(':operadora', $Operadora, PDO::PARAM_STR); 
	$SQL->bindParam(':acesso', $acesso, PDO::PARAM_STR);
	$SQL->bindParam(':data', $DataAtual, PDO::PARAM_STR);  
	$SQL->execute(); 
	
	if(empty($SQL)){
		echo MensagemAlerta('Erro', 'Ocorreu um problema ao cadastrar o usuário!', "danger", "index.php?p=usuario");
	}
	else{
		echo MensagemAlerta('Sucesso', 'Teste adicionado com sucesso!', "success", "index.php?p=criar-teste");
	}
		
		
	}
}

}else{
	echo Redirecionar('index.php');
}

}else{
	echo Redirecionar('login.php');
}	

?>