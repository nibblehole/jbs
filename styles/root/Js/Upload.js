//------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
//------------------------------------------------------------------------------
// Имя загружаемого файла
var $UploadFileName = null;
// Целевая форма
var $UploadFormName = null;
// Интервал закрытия окна загрузки
var $UploadInterval = null;
// хэш файла
var $Hash = null;
// переменная для хранения строки с инфой о загруженных файлах, при показе окна о загрузке
$UploadInfo = null;
// список всех загруженных файлов (для UploadDelete, удалить при отправке тикета)
let $Hashes = [];
//------------------------------------------------------------------------------
function UploadIframeOnLoad(){
	//------------------------------------------------------------------------------
	$Content = window.frames['UploadIframe'].document.body.innerHTML;
	//------------------------------------------------------------------------------
	$Answer = $Content.split('^');
	//------------------------------------------------------------------------------
	if($Answer.length > 2){
		//------------------------------------------------------------------------------
		$Name = $Answer[0]; // Имя файла
		$Size = $Answer[1]; // Размер файла в Кб.
		$Hash = $Answer[2]; // Хешь доступа к файлу на сервере
		//------------------------------------------------------------------------------
		// сохраяем хэш, если понадобится всё разом удялять
		$Hashes.push($Hash);
		//------------------------------------------------------------------------------
		if($Name.length > 20)
			$Name = SPrintF('%s...',$Name.substr(0,17));
		//------------------------------------------------------------------------------
		// дописываем файл (перенос строки добавляется только если уже был добавлен файл)
		$UploadInfo += SPrintF('<NOBR id="n_%s"><A style="font-size:11px;" href="javascript:ShowConfirm(\'Удалить файл?\',\'UploadDelete(\\\'%s\\\');\');">%s%s/%ukB</A></NOBR>',$Hash,$Hash,(($UploadInfo)?'<BR />':''),$Name,$Size);
		// пишем в строку для аплоада
		document.getElementById(SPrintF('Upload%sInfo',$UploadFileName)).innerHTML = $UploadInfo;
		//------------------------------------------------------------------------------
		var $Form = document.forms[$UploadFormName];
		//------------------------------------------------------------------------------
		//------------------------------------------------------------------------------
		$Input		= document.createElement('INPUT');
		$Input.type	= 'hidden';
		$Input.name	= SPrintF('%s[]',$UploadFileName);
		$Input.value	= $Hash;
		$Input.id	= SPrintF('input_%s',$Hash);
		//------------------------------------------------------------------------------
		$Form.appendChild($Input);
		//------------------------------------------------------------------------------
		//------------------------------------------------------------------------------
		$UploadInterval = window.setInterval('UploadHide()',100);
		//------------------------------------------------------------------------------
		// увеличиваем счётчик загруженных файлов
		var $Count = Number(document.getElementById(SPrintF('upload_%s_cnt',$UploadFileName)).value) + 1;
		document.getElementById(SPrintF('upload_%s_cnt',$UploadFileName)).value = $Count;
		//------------------------------------------------------------------------------
		// если счётчик больше или равен максимальному - заблокировать кнопку загрузки
		var $Max = Number(document.getElementById(SPrintF('upload_%s_max',$UploadFileName)).value);
		if($Count >= $Max){
			//------------------------------------------------------------------------------
			const $Button = document.getElementById(SPrintF('upload_%s',$UploadFileName));
			$Button.setAttribute('disabled','1');
			//------------------------------------------------------------------------------
		}
		//------------------------------------------------------------------------------
	}else{
		//------------------------------------------------------------------------------
		if($Content)
			ShowAlert('Не удалось загрузить файл. Вероятно, он слишком большого размера.','Warning');
		//------------------------------------------------------------------------------
		Debug($Content);
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
};

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
var $Image = new Image();
$Image.src = '/styles/root/Images/Icons/Upload.gif';
//------------------------------------------------------------------------------
function UploadDelete($Hash = false){
	//------------------------------------------------------------------------------
	// если хэш не задан - удаляем всё
	if(!$Hash){
		//------------------------------------------------------------------------------
		/* а собственно нахрена? страница же перезагрузится...
		 * nucomment by lissyara, 2020-03-10 in 09:41 MSK - а если добавленеи в существующий тикет - то нет */
		// проходит по значениям
		for (let $Hash of $Hashes)
			UploadDelete($Hash)
		//------------------------------------------------------------------------------
		// счётчик скидываем в ноль
		if(typeof document.getElementById(SPrintF('upload_%s_cnt',$UploadFileName)) !== null)
			document.getElementById(SPrintF('upload_%s_cnt',$UploadFileName)).value = 0;
		//------------------------------------------------------------------------------
		var $Count = 0;
		/* end uncomment */
		//------------------------------------------------------------------------------
	}else{
		//------------------------------------------------------------------------------
		// удаляем тег с иденфтикатором файла
		var $Input = document.getElementById(SPrintF('input_%s',$Hash));
		//------------------------------------------------------------------------------
		// возможно что тега нет (один из файлов удалили)
		if($Input){
			//------------------------------------------------------------------------------
			$Input.parentNode.removeChild($Input);
			//------------------------------------------------------------------------------
			// убираем NOBR c описанием файла
			var $NOBR = document.getElementById(SPrintF('n_%s',$Hash));
			$NOBR.parentNode.removeChild($NOBR);
			//------------------------------------------------------------------------------
			// если поле получилось пустое - вписыаем в него дефис
			if(document.getElementById(SPrintF('Upload%sInfo',$UploadFileName)).innerHTML == '')
				document.getElementById(SPrintF('Upload%sInfo',$UploadFileName)).innerHTML = '-';
			//------------------------------------------------------------------------------
			// убавляем счётчик на единицу
			var $Count = Number(document.getElementById(SPrintF('upload_%s_cnt',$UploadFileName)).value) - 1;
			document.getElementById(SPrintF('upload_%s_cnt',$UploadFileName)).value = $Count;
			//------------------------------------------------------------------------------
		}
		//------------------------------------------------------------------------------
		// если счётчик файлов меньше максимального - разблокировать кнопку загрузки
		var $Max = Number(document.getElementById(SPrintF('upload_%s_max',$UploadFileName)).value);
		if($Count < $Max){
			//------------------------------------------------------------------------------
			const $Button = document.getElementById(SPrintF('upload_%s',$UploadFileName));
			$Button.removeAttribute('disabled');
			//------------------------------------------------------------------------------
		}
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function UploadProgress(){
	//------------------------------------------------------------------------------
	// сохраняем содержимое строки с файлами
	$UploadInfo = document.getElementById(SPrintF('Upload%sInfo',$UploadFileName)).innerHTML;
	//------------------------------------------------------------------------------
	// если там дефис (первый запуск) то делаем строку пустой
	if($UploadInfo == '-')
		$UploadInfo = '';
	//------------------------------------------------------------------------------
	// вписываем в строку прогрессбар
	document.getElementById(SPrintF('Upload%sInfo',$UploadFileName)).innerHTML = '<IMG alt="Загрузка файла" width="65" height="16" src="/styles/root/Images/Icons/Upload.gif" />';
	//------------------------------------------------------------------------------
	var $Form = document.forms['UploadForm'];
	//------------------------------------------------------------------------------
	$Form.submit();
	//------------------------------------------------------------------------------
	$Form.Upload.value = '';
	//------------------------------------------------------------------------------
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function UploadShow($event,$Form,$FileName){
	//------------------------------------------------------------------------------
	var $Upload = document.getElementById('Upload');
	//------------------------------------------------------------------------------
	with($Upload.style){
		//------------------------------------------------------------------------------
		display = 'block';
		zIndex  = GetMaxZIndex() + 1;
		//------------------------------------------------------------------------------
		var $Body = document.body;
		//------------------------------------------------------------------------------
		var $OffsetX = $Body.clientWidth  - ($event.clientX + $Upload.offsetWidth);
		var $OffsetY = $Body.clientHeight - ($event.clientY + $Upload.offsetHeight);
		//------------------------------------------------------------------------------
		left = $Body.scrollLeft + $event.clientX + ($OffsetX < 0?$OffsetX:0);
		top  = $Body.scrollTop  + $event.clientY + ($OffsetY < 0?$OffsetY - 20:0) + 20;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	$UploadFormName = $Form;
	//------------------------------------------------------------------------------
	$UploadFileName = $FileName;
	//------------------------------------------------------------------------------
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function UploadHide(){
	//------------------------------------------------------------------------------
	window.clearInterval($UploadInterval);
	//------------------------------------------------------------------------------
	document.getElementById('Upload').style.display = 'none';
	//------------------------------------------------------------------------------
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function UploadHideFile($FileID){
	//------------------------------------------------------------------------------
	var $File = document.getElementById($FileID);
	$File.parentNode.removeChild($File);
	//------------------------------------------------------------------------------
}


