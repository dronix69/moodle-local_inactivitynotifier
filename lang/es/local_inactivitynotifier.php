<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish language strings for the Inactivity Notifier plugin.
 *
 * @package   local_inactivitynotifier
 * @copyright 2026 Daniel Ferrada
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['message_body'] = 'Hola {$a->firstname},

Notamos que no has visitado el curso "{$a->coursename}" durante {$a->days} días.

¡No te atrases! Haz clic en el siguiente enlace para continuar con tu aprendizaje:
{$a->courseurl}

¡Te esperamos!';
$string['message_body_html'] = '<p>Hola <strong>{$a->firstname}</strong>,</p>
<p>Notamos que no has visitado el curso <strong>"{$a->coursename}"</strong> durante <strong>{$a->days} días</strong>.</p>
<p>¡No te atrases! Haz clic en el siguiente enlace para continuar con tu aprendizaje:</p>
<p><a href="{$a->courseurl}">{$a->courseurl}</a></p>
<p>¡Te esperamos!</p>';
$string['message_small'] = 'Has estado inactivo en {$a} durante varios días.';
$string['message_subject'] = '¡Te extrañamos en {$a}!';
$string['messageprovider:inactivity_notification'] = 'Notificación de inactividad';
$string['mode_both'] = 'Popup + Email';
$string['mode_email_only'] = 'Solo email';
$string['mode_popup_only'] = 'Solo popup';
$string['pluginname'] = 'Notificador de Inactividad';
$string['privacy:metadata'] = 'El notificador de inactividad registra las notificaciones enviadas para evitar enviar notificaciones duplicadas con demasiada frecuencia.';
$string['privacy:metadata:courseid'] = 'El ID del curso en el cual el usuario estuvo inactivo.';
$string['privacy:metadata:tableexplanation'] = 'Esta tabla almacena registros de las notificaciones de inactividad enviadas para evitar saturar de mensajes a los estudiantes.';
$string['privacy:metadata:timesent'] = 'La marca de tiempo de cuándo se envió la notificación.';
$string['privacy:metadata:userid'] = 'El ID del usuario que recibió una notificación.';
$string['setting_email_body'] = 'Cuerpo personalizado del email (HTML)';
$string['setting_email_body_desc'] = 'Cuerpo HTML personalizado para el email. Dejar vacío para usar el predeterminado. Variables disponibles: {{firstname}}, {{coursename}}, {{days}}, {{courseurl}}';
$string['setting_email_subject'] = 'Asunto personalizado del email';
$string['setting_email_subject_desc'] = 'Asunto personalizado para el email. Dejar vacío para usar el predeterminado. Variables disponibles: {{firstname}}, {{coursename}}, {{days}}, {{courseurl}}';
$string['setting_enabled'] = 'Habilitar plugin';
$string['setting_enabled_desc'] = 'Cuando está deshabilitado, no se enviarán notificaciones.';
$string['setting_excluded_categories'] = 'Categorías excluidas';
$string['setting_excluded_categories_desc'] = 'Lista de IDs de categorías de cursos separadas por comas que se deben excluir de las notificaciones.';
$string['setting_excluded_courses'] = 'Cursos excluidos';
$string['setting_excluded_courses_desc'] = 'Lista de IDs de cursos separados por comas que se deben excluir de las notificaciones (ej. 12,34,56).';
$string['setting_inactivedays'] = 'Días de inactividad antes de notificar';
$string['setting_inactivedays_desc'] = 'Número de días que un estudiante debe estar inactivo antes de recibir una notificación.';
$string['setting_notification_mode'] = 'Modo de notificación';
$string['setting_notification_mode_desc'] = 'Selecciona cómo se entregan las notificaciones a los estudiantes. "Ambos" respeta las preferencias de mensajería de cada usuario.';
$string['setting_onlyvisible'] = 'Solo notificar en cursos visibles';
$string['setting_onlyvisible_desc'] = 'Si está marcado, los cursos ocultos serán ignorados.';
$string['setting_remind_frequency'] = 'Frecuencia de recordatorio';
$string['setting_remind_frequency_desc'] = 'Número mínimo de días a esperar antes de volver a enviar otra notificación de inactividad al mismo estudiante en el mismo curso.';
$string['task_send_notifications'] = 'Enviar notificaciones de inactividad a los estudiantes';
