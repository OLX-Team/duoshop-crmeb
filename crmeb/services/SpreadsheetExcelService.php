<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------


namespace crmeb\services;


use think\exception\ValidateException;

class SpreadsheetExcelService
{
    //
    private static $instance = null;
    //PHPSpreadsheet实例化对象
    private static $spreadsheet = null;
    //sheet实例化对象
    private static $sheet = null;
    private static $createsheet = null;
    //表头计数
    protected static $count;
    //表头占行数
    protected static $topNumber = 3;
    //表能占据表行的字母对应self::$cellkey
    protected static $cells;
    //表头数据
    protected static $data = [];
    //文件名
    protected static $title = '订单导出';
    //行宽
    protected static $width = 20;
    //行高
    protected static $height = 50;
    //保存文件目录
    protected static $path = 'phpExcel/';
    //总行数
    protected static $colum = 3;
    //设置style
    private static $styleArray = [
//         'borders' => [
//             'allBorders' => [
// //                PHPExcel_Style_Border里面有很多属性，想要其他的自己去看
//                // 'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,//边框是粗的
// //                'style' => \PHPExcel_Style_Border::BORDER_DOUBLE,//双重的
// //                'style' => \PHPExcel_Style_Border::BORDER_HAIR,//虚线
// //                'style' => \PHPExcel_Style_Border::BORDER_MEDIUM,//实粗线
// //                'style' => \PHPExcel_Style_Border::BORDER_MEDIUMDASHDOT,//虚粗线
// //                'style' => \PHPExcel_Style_Border::BORDER_MEDIUMDASHDOTDOT,//点虚粗线
//                 'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,//细边框
//                 // 'color' => ['argb' => 'FFFF0000'],
//             ],
//         ],
        'font' => [
            'bold' => true
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ]
    ];

    private function __construct(){}

    private function __clone(){}

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$spreadsheet = $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        }
        return self::$instance;
    }

    public function createOrActive($i = null)
    {
        if($i){
            self::$sheet = self::$spreadsheet->createSheet();
        }else{
            self::$sheet = self::$spreadsheet->getActiveSheet();
        }
        return $this;
    }

    /**
     *设置字体格式
     * @param $title string 必选
     * return string
     */
    public static function setUtf8($title)
    {
        return iconv('utf-8', 'gb2312', $title);
    }
    /**
     *  创建保存excel目录
     *  return string
     */
    public static function savePath()
    {
        if(!is_dir(self::$path)){
            if (mkdir(self::$path, 0700) == false) {
                return false;
            }
        }
        //年月一级目录
        $mont_path = self::$path.date('Ym');
        if(!is_dir($mont_path)){
            if (mkdir($mont_path, 0700) == false) {
                return false;
            }
        }
        //日二级目录
        $day_path = $mont_path.'/'.date('d');
        if(!is_dir($day_path)){
            if (mkdir($day_path, 0700) == false) {
                return false;
            }
        }
        return $day_path;
    }
    /**
     * 设置标题
     * @param $title string || array ['title'=>'','name'=>'','info'=>[]]
     * @param $Name string
     * @param $info string || array;
     * @param $funName function($style,$A,$A2) 自定义设置头部样式
     * @return $this
     */
    public function setExcelTile(array $data)
    {
        //设置参数
        if (is_array($data)) {
            if (isset($data['title'])) $title = $data['title'];
            if (isset($data['sheets'])) $sheets = $data['sheets'];
        }
        empty($title) ? $title = self::$title : self::$title = $title;

        if (empty($sheets)) $sheets = time();

        //设置Excel属性
        self::$spreadsheet->getProperties()
            ->setCreator("Neo")
            ->setLastModifiedBy("Neo")
            ->setTitle(self::setUtf8($title))
            ->setSubject($sheets)
            ->setDescription("")
            ->setKeywords($sheets)
            ->setCategory("");
        self::$sheet->setTitle($sheets);

        self::$sheet->mergeCells('A1:' . self::$cells . '1');   //合并表头单元格
        self::$sheet->getRowDimension('A')->setRowHeight(40);   //设置行高
        self::$sheet->setCellValue('A1', $title);               //负值
        self::$sheet->getStyle('A1')->getFont()->setName('黑体');
        self::$sheet->getStyle('A1')->getFont()->setSize(20);
        self::$sheet->getStyle('A1')->getFont()->setBold(true);
        self::$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); //设置左对齐

        if(isset($data['mark']) && !empty($data['mark'])){
            foreach ($data['mark'] as $k => $v){
                $i = $k + 2;
                self::$sheet->mergeCells('A'.$i.':' . self::$cells . $i);
                self::$sheet->setCellValue('A'.$i, $v);

                self::$sheet->getStyle('A'.$i)->getFont()->setName('宋体');
                self::$sheet->getStyle('A'.$i)->getFont()->setSize(16);
                self::$sheet->getStyle('A'.$i)->getFont()->setBold(true);
                self::$sheet->getStyle('A'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            }
        }

        return $this;
    }

    /**
     * 设置第二行标题内容
     * @param $info  array (['name'=>'','site'=>'','phone'=>123] || ['我是表名','我是地址','我是手机号码'] ) || string 自定义
     * @return string
     */
    private static function setCellInfo($info)
    {
        $content = ['操作者：', '导出日期：' . date('Y-m-d', time()), '地址：', '电话：'];
        if (is_array($info) && !empty($info)) {
            if (isset($info['name'])) {
                $content[0] .= $info['name'];
            } else {
                $content[0] .= isset($info[0]) ? $info[0] : '';
            }
            if (isset($info['site'])) {
                $content[2] .= $info['site'];
            } else {
                $content[2] .= isset($info[1]) ? $info[1] : '';
            }
            if (isset($info['phone'])) {
                $content[3] .= $info['phone'];
            } else {
                $content[3] .= isset($info[2]) ? $info[2] : '';
            }
            return implode(' ', $content);
        } else if (is_string($info)) {
            return empty($info) ? implode(' ', $content) : $info;
        }
    }
    /**
     * 设置头部信息
     * @param $data array
     * @return $this
     */
    public  function setExcelHeader($data,$topNumber)
    {
        $span = 'A';
        self::$topNumber = $topNumber;
        foreach ($data as $key => $value) {
            self::$sheet->getColumnDimension($span)->setWidth(self::$width);
            self::$sheet->setCellValue($span.self::$topNumber, $value);
            self::$sheet->getStyle($span.self::$topNumber)->getFont()->setSize(16);
            $span++;
        }
        $span = chr(ord($span) -1);
        self::$sheet->getRowDimension(self::$topNumber)->setRowHeight(25);
        self::$sheet->getStyle('A1:' . $span.self::$topNumber)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        self::$cells = $span;

        return  $this;
    }

    /**
     *
     * execl数据导出
     * @param  $data 需要导出的数据 格式和以前的可是一样
     *
     * 特殊处理：合并单元格需要先对数据进行处理
     */
    public function setExcelContent($data = [])
    {
        if (!empty($data) && is_array($data)) {
            $column = self::$topNumber+1;
            // 行写入
            foreach ($data as $key => $rows) {
                $span = 'A';
                // 列写入
                foreach ($rows as $keyName => $value) {
                    self::$sheet->setCellValue($span . $column, $value);
                    $span++;
                }
                $column++;
            }
            $span = chr(ord($span) -1);
            self::$colum = $column;
            self::$sheet->getDefaultRowDimension()->setRowHeight(self::$height);
            //设置内容字体样式
            self::$sheet->getStyle('A'.self::$topNumber .':'. $span.$column)->applyFromArray(self::$styleArray);
            //设置边框
            self::$sheet->getStyle('A1:' . $span.$column )->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            //设置自动换行
            self::$sheet->getStyle('A4:' . $span.$column)->getAlignment()->setWrapText(true);
        }
        return $this;
    }

    public function setExcelEnd(array $data)
    {
        if(!empty($data)){
            foreach ($data as $key => $value){
                $i = self::$colum + $key ;
                self::$sheet->mergeCells('A'.$i.':' . self::$cells.$i);
                self::$sheet->setCellValue('A'.$i, $value);

                self::$sheet->getStyle('A'.$i)->getFont()->setName('宋体');
                self::$sheet->getStyle('A'.$i)->getFont()->setSize(16);
                self::$sheet->getStyle('A'.$i)->getFont()->setBold(true);
                self::$sheet->getStyle('A'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            }
            self::$sheet->getStyle('A1:' .self::$cells.$i)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        return $this;
    }


    /**
     * 保存表格数据
     * @param $filename 文件名称
     * @param $suffix 文件后缀名
     * @param $path 是否保存文件文件
     * @return 保存文件：return string
     */
    public function excelSave($fileName = '',$suffix = 'xlsx',$path)
    {
        if(empty($fileName)) $fileName = date('YmdHis').time();
        if(empty($suffix)) $suffix = 'xlsx';
        // 重命名表（UTF8编码不需要这一步）
        if (mb_detect_encoding($fileName) != "UTF-8") $fileName = iconv("utf-8", "gbk//IGNORE", $fileName);

        $save_path = self::$path.$path;
        $root_path = app()->getRootPath().'public/'.$save_path;
        if(!is_dir($root_path)) mkdir($root_path, 0755,true);
        $spreadsheet = self::$spreadsheet;
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($root_path.'/'.$fileName.'.'.$suffix);

        return $save_path.'/'.$fileName.'.'.$suffix;
    }

    /**
     * TODO
     * @param $filePath 文件路径
     * @param array $sql  需要入库的字段 => excel表的列  例 [order_sn => 'B']
     * @param array $where  每条入库的条件 同上
     * @param int $startRow 有效数据从第几行开始
     * @return array
     * @author Qinii
     * @day 3/15/21
     */
    public function _import($filePath,array $sql,$where = [],$startRow = 1)
    {
        if(!file_exists($filePath)) return ;
        $ext = ucfirst(pathinfo($filePath, PATHINFO_EXTENSION));
        $ret = [];
        if (in_array($ext, ['Xlsx', 'Xls'])) {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($ext);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $row_count = $sheet->getHighestDataRow();//取得总行数
            for ($row = $startRow; $row <= $row_count; $row++) {
                $con = [];
                $item = [];
                $one = [];
//                $getK = $sheet->getCell('A'.$row)->getValue();
//                if ($getK) {
//                    $getV = $sheet->getCell('B'.$row)->getValue();
//                    $one[] = [$getK => $getV];
//                }
                if (!empty($where)) {
                    foreach ($where as $k => $v) {
                        $con_value = $sheet->getCell($v . $row)->getValue();
                        if ($con_value) {
                            $con[$k] = $con_value;
                        }
                    }
                    if (!empty($con)) $one['where'] = $con;
                }
                if ($con && !empty($sql)) {
                    foreach ($sql as $key => $value) {
                        $key_value = $sheet->getCell($value . $row)->getValue();
                        $item[$key] = $key_value ?? '';
                    }
                    if (!empty($item)) $one['value'] = $item;
                }
                if ($one) $ret[] = $one;
            }
        }

        return $ret;
    }

    /**
     * TODO 检测导入格式
     * @param $filePath
     * @param array $check
     * @return bool
     * @author Qinii
     * @day 5/7/21
     */
    public function checkImport($filePath,$check = [])
    {
        $ext = ucfirst(pathinfo($filePath, PATHINFO_EXTENSION));
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($ext);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        if(!empty($check)){
            foreach ($check as $s => $c){
                $_c = $sheet->getCell($s)->getValue();
                if($_c !== $c)  throw new ValidateException('表格"'.$s.'"不是"'.$c.'"不可导入');
            }
        }
        return true;
    }

}
