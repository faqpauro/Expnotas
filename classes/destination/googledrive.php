<?php

namespace local_expnotas\destination;

use local_expnotas\destination;

defined('MOODLE_INTERNAL') || die();

class googledrive extends destination {
    /**
     * Google Client.
     * @var \core\oauth2\client
     */
    private $client = null;

    /**
     * Google Drive format mimetype start
     */
    const DRIVE_MIMETYPE = 'application/vnd.google-apps.';
    /**
     * Google Drive format mimetypes
     */
    const TYPE_FOLDER = 'folder';

    /**
     * Additional scopes required for drive.
     */
    const SCOPES = 'https://www.googleapis.com/auth/drive';

    public function send() {
        global $CFG;
        $this->initialize_client();
        $service = new googledrive\rest($this->client);

        $directoryids = [];

        foreach (local_expnotas_get_files() as $file) {
            $filepath = $file->get_filepath();
            $directories = array_filter(explode('/', $filepath), function($part) {
                return !empty($part);
            });

            // Track how deep into the directory structure we are. This is the key
            // we'll use to keep track of previously created directory ids.
            $path = '/';
            // Track the parent directory so that we can look up it's id for creating
            // subdirectories in Google Drive.
            $parentpath = null;

            // Create each of the directories in Google Drive that we need.
            foreach ($directories as $directory) {

                // Update the current path for this file.
                $path .= "{$directory}/";
                if (!isset($directoryids[$path])) {

                    // This directory hasn't been created yet so let's go ahead and create it.
                    $parentid = !is_null($parentpath) ? $directoryids[$parentpath]['id'] : null;
                    try {
                        $mimetype = self::DRIVE_MIMETYPE.'folder';
                        // Unless it already  exists
                        if(!$drivefile = $this->search($service, $directory, $mimetype, $parentid)) {
                            $fileid = $this->create_folder($service, $directory);
                        } else {
                            // The response is a list of files, so the first and (hopefully) only element of the list is queried for its id
                            $fileid = $drivefile[0]->id;
                        }
                        $directoryids[$path] = ['id' => $fileid];
                        if($parentid) {
                            $this->move_file($service, $fileid, $parentid);
                        }
                    } catch (Exception $e) {
                        // TODO: Exception handling
                        // throw new portfolio_plugin_exception('sendfailed', 'portfolio_gdocs', $directory);
                    }
                }

                $parentpath = $path;
            }
            try {
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $parentid = $directoryids[$filepath]['id'];
                if($drivefile = $this->search($service, $filename, $mimetype, $parentid)) {
                    // The response is a list of files, so the first and (hopefully) only element of the list is queried for its id
                    $fileid = $drivefile[0]->id;
                    $this->client->setHeader('Content-Type: ' . $file->get_mimetype());
                    $this->client->setHeader('Content-Length: ' . $file->get_filesize());
                    $service->call('update_content', ['fileId' => $fileid, 'uploadType' => 'media'], $file->get_content());
                    /*
                    if(!$folderid = search($service, 'old', $mimetype, $parentid)) {

                        //Create the folder
                        $folderid = create_folder('old');
                    }
                    move_file($drivefile, $folderid, $parentid);*/
                } else {
                    // Create file.
                    $metadata = json_encode([
                        'name' => $filename,
                        'mimetype' => $mimetype,
                    ]);
                    $this->client->setHeader('X-Upload-Content-Type: ' . $file->get_mimetype());
                    $this->client->setHeader('X-Upload-Content-Length: ' . $file->get_filesize());
                    $headers = $service->call('upload_metadata', ['uploadType' => 'resumable'], $metadata);
                    $uploadid;
                    // Google returns a location header with the location for the upload.
                    foreach ($headers as $header) {
                        if (stripos($header, 'x-guploader-uploadid') === 0) {
                            $uploadid = trim(substr($header, strpos($header, ':') + 1));
                        }
                    }
                    try {
                        $drivefile = $service->call('upload_content', ['uploadType' => 'resumable', 'upload_id' => $uploadid], $file->get_content(), $file->get_mimetype());
                    } catch ( Exception $e ) {
                        // TODO: Exception handling
                        // throw new portfolio_plugin_exception('sendfailed', 'portfolio_gdocs', $file->get_filename());
                    }

                    if($drivefile) {
                        $fileid = $drivefile->id;
                        $this->move_file($service, $fileid, $parentid);
                    }
                }

            } catch ( Exception $e ) {
                throw new portfolio_plugin_exception('sendfailed', 'portfolio_gdocs', $file->get_filename());
            }
        }
    }

    public function search($service, $filename, $mimetype, $parentid) {
        $queryparent = !is_null($parentid) ? $parentid : 'root';
        $querytemplate = "name = '%s' and mimeType = '%s' and '%s' in parents";
        $query = sprintf($querytemplate, $filename, $mimetype, $queryparent);
        try {
            $elements = $service->call('search', ['q' => $query]);
        } catch (Exception $e ) {
            // TODO: Exception handling
            // 'Something happened while searching: ' . $e->getMessage();
        }
        return $elements->files;
    }

    public function create_folder($service, $name) {
        $folder = [
            'name' => $name,
            'mimeType' => self::DRIVE_MIMETYPE . self::TYPE_FOLDER,
        ];
        $drivefile = $service->call('create', ['fields' => 'id'], json_encode($folder));
        $folderid = $drivefile->id;
        return $folderid;
    }

    public function move_file($service, $id, $newparent, $oldparent = 'root') {
        $updateparams = [
            'fileid' => $id,
            'addParents' => $newparent,
            'removeParents' => $oldparent,
        ];
        try {
            $service->call('update', $updateparams, ' ');
        } catch ( Exception $e ) {

            // TODO: Exception handling
            // 'Something happened while placing files into folders: ' . $e->getMessage();
        }
    }

    private function initialize_client() {
        $issuer = \core\oauth2\api::get_issuer(get_config('googledocs', 'issuerid'));
        if ($issuer->is_system_account_connected()) {
            $this->client = \core\oauth2\api::get_system_oauth_client($issuer);
        } else {
            // TODO: Exception handling
            // 'The system account is not connected';
        }
    }


}

/**
 * Callback to get the required scopes for system account.
 *
 * @param \core\oauth2\issuer $issuer
 * @return string
 * @package local_expnotas
 */
function destination_googledocs_oauth2_system_scopes(\core\oauth2\issuer $issuer) {
    if ($issuer->get('id') == get_config('googledocs', 'issuerid')) {
        return 'https://www.googleapis.com/auth/drive';
    }
    return '';
}
