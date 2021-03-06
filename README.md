# Courses by Role

This moodle block plugin lists the courses that you are enrolled in grouped in headings of the ROLE that you are in that course, then shown by category under than role heading (nesting of categories not supported).

You can use it to list the courses you are a student of separately to the list of courses you are a teacher of, or a manager, or some custom role you have defined.

## Instalation

Drop the plugin into your /blocks/ folder and install as usual (ensure the folder is called `courses_by_role`)

or `cd ./blocks && git clone https://github.com/frumbert/courses_by_role.git courses_by_role`

## Configuration

The block has global settings (Site Administration > Plugins > Blocks > Courses by Role). On this page will be a list of the roles defined on your system from which you can select the roles you want to consider when rendering this block. Next to that are custom role labels that you can apply - so that the block can display text labels that are different to your internal role names. For instance, you might want to name your student role 'Current Enrolments' instead of 'Student'. Following the naming pattern guidelines on that page.

Drop the block onto your homepage or dashboard etc.

## Licence

GPL3, same as Moodle