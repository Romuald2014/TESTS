<?php

	class PolygonsCollision {
		/**
		 * This class check if two 2D polygons have intersections
		 * Polygons defined by array of points in euclidean coordinates [[x1,y1], [x2,y2]..]
		 * In return there is bool variable $collision (true or false)
		 */
		
		public $collision;
		
		function __construct($p1, $p2) {
			$this->collision = self::collide($p1, $p2);
		}
	
		function minmax($shape) {
			return array(
				'min X' => min(array_column($shape, 0)),
				'min Y' => min(array_column($shape, 1)),
				'max X' => max(array_column($shape, 0)),
				'max Y' => max(array_column($shape, 1))
			);
		}
		
		function getSlopeCoefs($segment)
			/**
			 * This function calculates slope coefficients a, b and type of slope based on segment
			 * Segment is an array [[x1, y1],[x2, y2]]
			 * In return there is an array ['type' => 'slope', 'coefs' => [a, b]]
			 *  ['type' => 'horizontal', 'const' => y]
			 * *['type' => 'vertical', 'const' => x]
			 */
		{
			if (($segment[0][0] - $segment[1][0]) == 0)
				return ['type' => 'vertical', 'const' => $segment[0][0], 'coefs' => [0, 0]];
			elseif (($segment[0][1] - $segment[1][1]) == 0)
				return ['type' => 'horizontal', 'const' => $segment[0][1], 'coefs' => [0, $segment[0][1]]];
			else
				$a = ($segment[0][1] - $segment[1][1]) / ($segment[0][0] - $segment[1][0]);
				$b = $segment[0][1] - ($a * $segment[0][0]);
				return ['type' => 'slope', 'coefs' => [$a, $b]];
		}
		
		function intersect($segment1, $segment2)
		{
			/**
			 * This function check if two segments have intersection
			 * Segment is an array of two points [[x1, y1],[x2, y2]]
			 * In return there is an array ['intersect' => false / true, 'point' => [$x, $y]];
			 */
			// get coefficients of segments
			$c1 = self::getSlopeCoefs($segment1);
			$c2 = self::getSlopeCoefs($segment2);
			
			//Set segments variables:
			$type1 = $c1['type'];
			$type2 = $c2['type'];
			
			// linear coefficients a, b from (y = a*x +b)
			$a1 = $c1['coefs'][0];
			$b1 = $c1['coefs'][1];
			$a2 = $c2['coefs'][0];
			$b2 = $c2['coefs'][1];
			
			// minimum and maximum coordinates
			$mms1 = self::minmax($segment1);
			$minX1 = $mms1['min X'];
			$minY1 = $mms1['min Y'];
			$maxX1 = $mms1['max X'];
			$maxY1 = $mms1['max Y'];
			
			$mms2 = self::minmax($segment2);
			$minX2 = $mms2['min X'];
			$minY2 = $mms2['min Y'];
			$maxX2 = $mms2['max X'];
			$maxY2 = $mms2['max Y'];
			
			if(($type1 == $type2) and $type1 != 'slope') // case vertical - vertical or horizontal - horizontal
			{
				if ($c1['const'] != $c2['const'])
					return ['intersect' => false, 'point' => []];
				elseif (
					$type1 == 'horizontal' and
					$maxX1 > $minX2 and $maxX1 < $maxX2 or
					$maxX2 > $minX1 and $maxX1 > $maxX2
				)
					return ['intersect' => true, 'point' => []];
				elseif (
					$type1 == 'vertical' and
					$maxY1 > $minY2 and $maxY1 < $maxY2 or
					$minY1 < $maxY2 and $maxY1 > $maxY2
				)
					return ['intersect' => true, 'point' => []];
				else
					return ['intersect' => false, 'point' => []];
			} else {	// case one or both slope
				if($type1 == 'vertical')
				{
					$x = $c1['const'];
					$y = $a2 * $x + $b2;
				} elseif($type2 == 'vertical')
				{
					$x = $c2['const'];
					$y = $a1 * $x + $b1;
				} elseif($a1 == $a2) // case slope - slope parallel
				{
					if($b1 == $b2) // case the same line
					{
						if (
							($a1 > 0 and
								(
									($maxX1 > $minX2 and $maxY2 > $maxY1) or
									($maxX2 > $minX1 and $maxY2 < $maxY1)
								)
							) or
							($a1 < 0 and
								(
									($maxX1 > $minX2 and $maxY2 > $minY1) or
									($maxX2 > $minX1 and $maxY2 > $maxY1)
								)
							)
						)
							return ['intersect' => true, 'point' => []];
						else
							return ['intersect' => false, 'point' => []];
					} else
						return ['intersect' => false, 'point' => []];
				} else // case slope - slope or slope - horizontal
				{
					$x = ($b2 - $b1) / ($a1 - $a2);
					$y = $a1 * $x + $b1;
				}
				
				// check if intersection belongs to both segments
				if (
					($x >= $minX1 and $x <= $maxX1) and
					($x >= $minX2 and $x <= $maxX2) and
					($y >= $minY1 and $y <= $maxY1) and
					($y >= $minY2 and $y <= $maxY2)
				)
					return ['intersect' => true, 'point' => [$x, $y]];
				else
					return ['intersect' => false, 'point' => [$x, $y]];
			}
		}
		
		function shape2segment($shape)
		{
			/**
			 * This function chops a shape into array of segments between shape points
			 * In return there is an array [[[$x1,$y1],[x2,y2]], [[x2,y2],[x3,y3]...];
			 */
			$a = $shape[0];
			$segment = [];
			$count = count($shape);
			for ($i = 1; $i < $count; $i++)
			{
				$b = $shape[$i];
				$segment[] = [$a, $b];
				$a = $b;
			}
			$segment[] = [$b, $shape[0]];
			return $segment;
		}
		
		
		function collide($shape1, $shape2)
		{
			/**
			 * This function check if two shapes have at least one intersection
			 * In return there is bool var
			 */
			$minmax1 = self::minmax($shape1);
			$minmax2 = self::minmax($shape2);
			
			if (
				$minmax1['max X'] < $minmax2['min X'] or
				$minmax1['max Y'] < $minmax2['min Y'] or
				$minmax1['min Y'] > $minmax2['max Y'] or
				$minmax1['min X'] > $minmax2['max X']
				)
				return false;
			else {
				$segments1 = self::shape2segment($shape1);
				$segments2 = self::shape2segment($shape2);
				$count1 = count($segments1);
				$count2 = count($segments2);
				for ($i = 0; $i < $count1 - 1; $i++)
				{
					for ($j = 0; $j < $count2 - 1; $j++)
					{
						if (self::intersect($segments1[$i], $segments2[$j])['intersect'])
							return true;
					}
				}
				return false;
			}
		}
	}
	

	// Tests
	
	$shapes2D_1 = array(
		[[2,0],[4,2],[2,4],[0,2]],
		[[6,0],[8,2],[7,3],[5,1]],
		[[3,2],[4,3],[4,6],[3,6]],
		[[6,2],[6,4.5],[3.5,4.5]],
		[[0,2],[2,4],[1,5],[-1,3]],
	);
	
	$shapes2D = array(
		[[0,2],[2,4],[-1,4],[-1,6], [1,7],[0,8],[-2,6],[-3,3]],
		[[0.5,5],[4,5],[4,9]],
		[[-2,4],[-2,9],[-3,9],[-3,4]]
	);
	
	
	$count = count($shapes2D);
	for ($i = 0; $i < $count - 1; $i++)
	{
		for ($j = $i + 1; $j < $count; $j++)
		{
			$polygonsCollision = new PolygonsCollision($shapes2D[$i], $shapes2D[$j]);
			echo "Shape $i vs Shape $j <br>";
			if ($polygonsCollision->collision)
				echo 'intersects<br><br>';
			else
				echo 'not intersects<br><br>';
		}
	}
	