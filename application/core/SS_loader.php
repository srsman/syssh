<?php
class SS_Loader extends CI_Loader{
	
	var $main_view_loaded=FALSE;
	var $sidebar_loaded=FALSE;

	var $require_head=TRUE;//页面头尾输出开关（含menu）
	var $require_menu=TRUE;//顶部蓝条/菜单输出开关
	var $view_data=array();//要传递给视图的参数
	
	var $sidebar_data='';
	
	function __construct(){
		parent::__construct();
	}

	function getViewData($param=NULL){
		if(isset($param)){
			return $this->view_data[$param];
		}else{
			return $this->view_data;
		}
	}
	
	function addViewData($name,$value){
		$this->view_data+=array($name=>$value);
	}
	
	function addViewArrayData(array $array){
		$this->view_data+=$array;
	}
	
	/**
	 * @param $return: FALSE:进入输出缓存,TRUE:作为字符串返回,'sidebar':加入边栏
	 */
	function view($view, array $vars = array(), $return = FALSE){
		
		$vars=array_merge($vars,$this->getViewData());//每次载入视图时，都将当前视图数据传递给他一次
		
		if($return === 'sidebar'){
			$this->sidebar_data.=parent::view($view, $vars, TRUE);
		}else{
			return parent::view($view, $vars, $return);
		}
	}

	/*
		将$array输出成一个表格
		$array:数据数组
		结构：
		Array
		(
			[_field] => Array
				(
					[字段名1] => array(
						'html'=>字段标题
						'attrib'=>字段html标签属性
					),
					[字段名2] => array(
						'html'=>字段标题
						'attrib'=>字段html标签属性
					)
				)

			[第一行行号] => Array
				(
					[字段名1] =>  array(
						'html'=>字段值
						'attrib'=>字段html标签属性
					),
					[字段名2] =>  array(
						'html'=>字段值
						'attrib'=>字段html标签属性
					)
				)
			...
		)
	*/
	function arrayExportTable(array $array,$menu=NULL,$surroundForm=false,$surroundBox=true,array $attributes=array(),$show_line_id=false,$trim_columns=false){

		if($trim_columns){
			$table_head['_field']=$array['_field'];
			$table_body=array_slice($array,1);

			$column_is_empty=array();

			foreach($table_head['_field'] as $field_name => $field_title){
				$column_is_empty[$field_name]=true;
			}

			foreach($table_body as $line_id => $line){
				foreach($line as $field_name => $field){
					if((is_array($field) && (strip_tags($field['html'])!='')) || (!is_array($field) && strip_tags($field)!='')){
						$column_is_empty[$field_name]=false;
					}
				}
			}

			foreach($array as $line_id => $line){
				foreach($line as $field_name => $field){
					if($column_is_empty[$field_name]){
						unset($array[$line_id][$field_name]);
					}
				}
			}
		}

		if($surroundForm){
			echo '<form method="post">'."\n";
		}

		if(isset($menu['head'])){
			echo '<div class="contentTableMenu"';

			foreach($attributes as $attribute_name => $attribute_value){
				echo ' '.$attribute_name.'="'.$attribute_value.'"';
			}

			echo '>'."\n".$menu['head'].'</div>'."\n";
		}

		if($surroundBox){
			echo '<div class="contentTableBox">'."\n";
		}

		echo '<table class="contentTable" cellpadding="0" cellspacing="0"';

		foreach($attributes as $attribute_name => $attribute_value){
			echo ' '.$attribute_name.'="'.$attribute_value.'"';
		}

		echo '>'."\n".
		'	<thead><tr>'."\n";

		if($show_line_id){
			echo '<td width="40px">&nbsp;</td>';
		}

		$fields=$array['_field'];unset($array['_field']);

		foreach($fields as $field_name=>$value){
			echo '<th field="'.$field_name.'"'.(is_array($value) && isset($value['attrib'])?' '.$value['attrib']:'').'>'.(is_array($value)?$value['html']:$value).'</td>';
		}

		echo "	</th></thead>"."\n";

		echo "	<tbody>"."\n";

		$line_id=1;
		foreach($array as $linearray){
			if($line_id%2==0){
				$tr='class="oddLine"';
			}else{
				$tr='';
			}
			echo "<tr ".$tr.">";

			if($show_line_id){
				echo '<td style="text-align:center">'.($line_id+option('list/start')).'</td>';
			}

			foreach($fields as $field_name=>$value){
				$html=is_array($linearray[$field_name])?$linearray[$field_name]['html']:$linearray[$field_name];
				if(empty($html)){
					$html='&nbsp;';
				}

				echo '<td field="'.$field_name.'"'.(is_array($linearray[$field_name]) && isset($linearray[$field_name]['attrib'])?' '.$linearray[$field_name]['attrib']:'').'>'.$html.'</td>';
			}

			echo "</tr>";
			$line_id++;
		}
		echo "	</tbody>"."\n";
		echo "</table>"."\n";

		if($surroundBox){
			if(isset($menu['foot'])){
				echo
				'<div class="contentTableFoot"';

				foreach($attributes as $attribute_name => $attribute_value){
					echo ' '.$attribute_name.'="'.$attribute_value.'"';
				}

				echo '>'."\n".
					$menu['foot'].
				'</div>'."\n";
			}

			echo "</div>"."\n";
		}

		if($surroundForm){
			echo '</form>'."\n";
		}
	}

	function arrayExportExcel(array $array){
		//TODO arrayExportExcel
		require APPPATH.'third_party/PHPExcel/PHPExcel.php';
		require APPPATH.'third_party/PHPExcel/PHPExcel/Writer/Excel5.php';

		$objExcel = new PHPExcel();
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		$objProps = $objExcel->getProperties();
		$objProps->setCreator($_SESSION['username']);
		$objProps->setLastModifiedBy($_SESSION['username']);
		/*$objProps->setTitle($file_title);
		$objProps->setSubject("Office XLS Test Document, Demo"); 
		$objProps->setDescription("Test document, generated by PHPExcel.");
		$objProps->setKeywords("office excel PHPExcel");
		$objProps->setCategory("Test");*/

		$objExcel->setActiveSheetIndex(0); 
		$objActSheet = $objExcel->getActiveSheet();  

		//设置当前活动sheet的名称  
		$objActSheet->setTitle('sheet1');  

		//设置单元格内容  由PHPExcel根据传入内容自动判断单元格内容类型  
		$objActSheet->setCellValue('A1', '字符串内容');  // 字符串内容  
		$objActSheet->setCellValue('A2', 26);            // 数值  
		$objActSheet->setCellValue('A3', true);          // 布尔值  
		$objActSheet->setCellValue('A4', '=SUM(A2:A2)'); // 公式   

		$fields=$array['_field'];unset($array['_field']);

		foreach($fields as $field_name=>$value){
			echo '<td field="'.$field_name.'"'.(is_array($value) && isset($value['attrib'])?' '.$value['attrib']:'').'>'.(is_array($value)?$value['html']:$value).'</td>';
		}

		foreach($array as $linearray){
			foreach($fields as $field_name=>$value){
				$html=is_array($linearray[$field_name])?$linearray[$field_name]['html']:$linearray[$field_name];
				echo '<td field="'.$field_name.'"'.(is_array($linearray[$field_name]) && isset($linearray[$field_name]['attrib'])?' '.$linearray[$field_name]['attrib']:'').'>'.$html.'</td>';
			}

		}
		$objWriter->save('php://output');
	}

}
?>