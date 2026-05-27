<?php
function e($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function school_name()
{
  return 'Naevis Inter High School';
}

function jhs_subjects()
{
  return [
    'fil' => 'Filipino',
    'eng' => 'English',
    'math' => 'Mathematics',
    'scie' => 'Science',
    'ap' => 'Araling Panlipunan',
    'esp' => 'ESP',
    'tle' => 'TLE',
    'mapeh' => 'MAPEH'
  ];
}

function shs_subjects()
{
  return [
    'oral_comm' => 'Oral Communication',
    'reading_writing' => 'Reading and Writing',
    'komunikasyon' => 'Komunikasyon',
    'contemporary' => 'Contemporary Arts',
    'personal_dev' => 'Personal Development',
    'earth_science' => 'Earth Science',
    'pagbabasa' => 'Pagbasa at Pagsusuri',
    'understanding' => 'Understanding Culture',
    'earth_life' => 'Earth and Life Science',
    'media_info' => 'Media and Information Literacy',
    'applied1' => 'Applied Subject 1',
    'applied2' => 'Applied Subject 2',
    'spec1' => 'Specialized Subject 1',
    'spec2' => 'Specialized Subject 2'
  ];
}

function grade_program($grade)
{
  return ((int)$grade >= 11) ? 'Senior High School' : 'Junior High School';
}

function final_grade($grades)
{
  $valid = array_filter($grades, fn($g) => $g !== null && $g !== '' && is_numeric($g));
  if (count($valid) === 0) return null;
  return round(array_sum($valid) / count($valid), 2);
}

function remarks($grade)
{
  if ($grade === null || $grade === '') return '';
  return ((float)$grade >= 75) ? 'Passed' : 'Failed';
}
