NextGen 3D Filmstrip
====================

Perspective filmstrip deck for Tutors or Subjects.

Shortcodes
----------
[ngt_filmstrip source="tutors"]
[ngt_filmstrip source="subjects" limit="10"]
[ngt_filmstrip source="tutors" subject="mathematics"]
[ngt_filmstrip source="manual" manual="Ada|Maths lead|https://.../a.jpg|https://site/find-a-tutor/"]

Attributes: source (tutors|subjects|manual), limit, subject, title, subtitle, autoplay, loop, manual, class

Data sources
------------
Tutors: bi_get_carousel_tutors() / Companion UI provider
Subjects: NGSW_Catalog → subject taxonomy → NGC_Subjects_CMS → bi_get_subject_tracks

Admin: Settings → NextGen Filmstrip
