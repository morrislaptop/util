<?php
/**
* GridHelper allows you to create HTML valid grids using tables from an array of data.
* An element is called to actually display each element in the array.
* 
* The grid helper will automatically finish and start new rows based on the number of 
* columns you want. On the last row it will also, finish the number of columns with 
* empty cells.
* 
* Each row will be available in the element as $data. The element should contain the <td>
* tag (this is so it can set class names and whatever else you wish)
* 
* Example Usage (view):
* echo $grid->grid($products, array('element' => 'grid_product', 'cols' => 3))
* 
* Settings:
*
* element
* The element called to display each row in your data. 
* default: "grid" (this most likely wont be in your view!)
*
* cols
* The number of columns you want in your grid.
* default: 4

* @author Craig Morris <craig@waww.com.au>
* @link http://www.waww.com.au/open-source-projects/grid
* @link http://gist.github.com/120840
* @copyright (c) 2009 Craig Morris
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*/
class GridHelper extends AppHelper
{
	function grid($rows, $opts)
	{
		$view =& ClassRegistry:: getObject('view');
		$default = array(
			'element' => 'grid',
			'cols' => 4
		);
		$opts = array_merge($default, $opts);
		extract($opts);
		
		?>
		<tr>
			<?php
				$i = 1;
				$first = true;
				$last = end($rows);
				foreach ($rows as $row)
				{
					echo $view->element($element, array('data' => $row));
					if ( $i % $cols === 0 && $row != $last ) {
						echo '</tr><tr>';
					}
					$first = false;
					$i++;
				}
				
				// $i is always one more than the cells we have made
				$i--;
				
				$soFar = $i % $cols;
				$toGo = $cols - $soFar;
				
				if ( $soFar ) for ( $j = 0; $j < $toGo; $j++ ) {
					echo '<td>&nbsp;</td>';
				}
			?>
		</tr>
		<?php
	}
}
?>