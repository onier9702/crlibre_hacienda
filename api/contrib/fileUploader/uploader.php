<?php
/*
 * Copyright (C) 2017-2025 CRLibre <https://crlibre.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function doTest()
{
    return "Test :)";
}

function uploadCert()
{
    modules_loader("files");
    $dets = files_upload("hacienda", false, "p12");
    return $dets;
}

function deleteCert()
{
    modules_loader("files");
    global $user;

    $q = sprintf(
        "DELETE FROM files WHERE idUser = '%s' AND type = 'hacienda' AND name LIKE '%%.p12'",
        db_escape($user->idUser)
    );
    $removedRows = db_query($q, 0);

    if ($removedRows === ERROR_DB_NO_RESULTS_FOUND)
        $removedRows = 0;

    if (!is_int($removedRows))
        return ERROR_FILES_UPLOAD_ERROR;

    $removedFiles = 0;
    foreach (glob(files_createPath($user->idUser, "hacienda") . "*.p12") ?: [] as $path) {
        if (unlink($path))
            $removedFiles++;
    }

    return [
        'removedRows'  => $removedRows,
        'removedFiles' => $removedFiles
    ];
}

function uploadXml()
{
    modules_loader("files");
    $dets = files_upload("hacienda", false, "xml");
    return $dets;
}
