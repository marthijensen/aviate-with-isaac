<?php

require_once __DIR__ . '/supabase.php';

function getActiveStudents()
{
    return supabaseRequest(
        'students'
        . '?status=eq.active'
        . '&select=id,first_name,last_name,created_at,status'
        . '&order=created_at.asc'
    );
}

function getActiveStudentCount()
{
    $students = getActiveStudents();

    return count($students);
}