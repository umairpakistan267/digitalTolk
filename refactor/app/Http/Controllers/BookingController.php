<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use Illuminate\Support\Facades\Log;
use Exception;
/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            $response = [];

            if ($userId = $request->get('user_id')) {
                $response = $this->repository->getUsersJobs($userId);
            } elseif ($this->isAdminOrSuperAdmin($request->__authenticatedUser)) {
                $response = $this->repository->getAll($request);
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Error in BookingController@index: ' . $e->getMessage());

            // Return a generic error response
            return response()->json([
                'error' => 'Ett fel inträffade vid bearbetningen av din begäran.'
            ], 500);
        }
    }


    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        try {
            // Find the job with related data
        $job = $this->repository->findJobWithRelations($id, ['translatorJobRel.user']);


            if (!$job) {
                return response()->json([
                    'error' => 'Jobb hittades inte.',
                    'message' => "Inget jobb hittades med ID $id."
                ], 404);
            }

            return response()->json($job, 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error("Error in BookingController@show: {$e->getMessage()}");

            // Return a generic error response
            return response()->json([
                'error' => 'Ett fel uppstod vid hämtning av jobbet.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        try {
            // Get all input data
            $data = $request->all();

            // Call repository to handle the storage logic
            $response = $this->repository->store($request->__authenticatedUser, $data);

            // Return a successful response
            return response()->json($response, 201); // 201: Created
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error("Error in BookingController@store: {$e->getMessage()}");

            // Return an error response
            return response()->json([
                'error' => 'Ett fel uppstod vid lagring av bokningen.'
            ], 500);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        try {
            // Extract data and authenticated user
            $data = $request->except(['_token', 'submit']); // Exclude unnecessary fields
            $currentUser = $request->__authenticatedUser;

            // Delegate the update operation to the repository
            $response = $this->repository->updateJob($id, $data, $currentUser);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            \Log::error("Error in BookingController@update: {$e->getMessage()}");

            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod vid uppdatering av jobbet.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        try {
            // Retrieve admin email from configuration
            $adminSenderEmail = config('app.adminemail');

            // Get all request data
            $data = $request->all();

            // Call repository method to handle the logic
            $response = $this->repository->storeJobEmail($data);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log the error
            \Log::error("Error in BookingController@immediateJobEmail: {$e->getMessage()}");

            // Return error response
            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod vid skickandet av det omedelbara jobbmejlet.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            if (!$userId) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'User ID is required.',
                ], 400);
            }

            $response = $this->repository->getUsersJobsHistory($userId, $request);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            \Log::error("Error in BookingController@getHistory: {$e->getMessage()}");

            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod vid hämtning av jobbhistorik.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        try {
            $data = $request->all();
            $user = $request->__authenticatedUser;

            // Ensure the user and data are valid
            if (!$user) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Användaren är inte autentiserad.',
                ], 401);
            }

            if (empty($data['job_id'])) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Jobb-ID krävs.',
                ], 400);
            }

            // Delegate to repository
            $response = $this->repository->acceptJob($data, $user);

            return response()->json($response);
        } catch (\Exception $e) {
            // Log the exception for debugging purposes
            \Log::error('Fel vid accepterande av jobbet: ' . $e->getMessage(), [
                'data' => $request->all(),
                'user' => $request->__authenticatedUser ?? null,
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod vid accepterandet av jobbet.'
            ], 500);
        }
    }

    public function acceptJobWithId(Request $request)
    {
        try {
            $jobId = $request->get('job_id');
            $user = $request->__authenticatedUser;

            if (!$jobId) {
                return response([
                    'status' => 'fail',
                    'message' => 'Jobb-ID krävs.',
                ], 400);
            }

            $response = $this->repository->acceptJobWithId($jobId, $user);

            return response()->json($response);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Error in BookingController@acceptJobWithId: ' . $e->getMessage(), [
                'job_id' => $request->get('job_id'),
                'user_id' => $request->__authenticatedUser->id,
            ]);

            return response([
                'status' => 'error',
                'message' => 'Ett fel uppstod vid behandlingen av förfrågan.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        try {
            $data = $request->all();
            $user = $request->__authenticatedUser;

            // Validate the presence of `job_id`
            if (empty($data['job_id'])) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Job ID saknas. Avbokningen kunde inte genomföras.'
                ], 400);
            }

            // Attempt to cancel the job via the repository
            $response = $this->repository->cancelJobAjax($data, $user);

            // Check if the repository returned a valid response
            if (!$response || $response['status'] !== 'success') {
                throw new \Exception($response['message'] ?? 'Ett oväntat fel inträffade vid avbokningen.');
            }

            // Return the success response
            return response()->json($response, 200);

        } catch (\Exception $e) {
            // Log the error details
            Log::error('Error cancelling job', [
                'error' => $e->getMessage(),
                'user_id' => $request->__authenticatedUser->id ?? null,
                'request_data' => $request->all(),
            ]);

            // Return a JSON error response
            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod vid avbokningen. Försök igen senare.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        try {
            $data = $request->all();
    
            // Validate required fields
            if (!isset($data['job_id'])) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Jobb-ID krävs.',
                ], 400);
            }
    
            $response = $this->repository->endJob($data);
    
            // Return a successful response
            return response()->json($response, 200);
    
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error ending job', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
    
            // Return an error response
            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod när jobbet skulle avslutas. Vänligen försök igen senare.'
            ], 500);
        }
    }
    

    public function customerNotCall(Request $request)
    {
        try {
            $data = $request->all();
    
            $response = $this->repository->customerNotCall($data);
    
            return response()->json($response);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in customerNotCall', ['error' => $e->getMessage(), 'data' => $data]);
    
            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod vid behandlingen av din begäran. Försök igen senare.'
            ], 500);
        }
    }
    

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        try {
            $user = $request->__authenticatedUser;

            if (!$user) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Användaruppgifter saknas.' 
                ], 400);
            }

            $response = $this->repository->getPotentialJobs($user);

            if (empty($response)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Inga potentiella jobb hittades.',
                    'jobs' => []
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Potentiella jobb hämtades framgångsrikt.',
                'jobs' => $response
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in getPotentialJobs', ['error' => $e->getMessage(), 'user' => $request->__authenticatedUser]);

            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod när potentiella jobb skulle hämtas. Försök igen senare.' 
            ], 500);
        }
    }


    public function distanceFeed(Request $request)
    {
        try{
        $data = $request->all();

        if (isset($data['distance']) && $data['distance'] != "") {
            $distance = $data['distance'];
        } else {
            $distance = "";
        }
        if (isset($data['time']) && $data['time'] != "") {
            $time = $data['time'];
        } else {
            $time = "";
        }
        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];
        }

        if (isset($data['session_time']) && $data['session_time'] != "") {
            $session = $data['session_time'];
        } else {
            $session = "";
        }

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }
        
        if ($data['manually_handled'] == 'true') {
            $manually_handled = 'yes';
        } else {
            $manually_handled = 'no';
        }

        if ($data['by_admin'] == 'true') {
            $by_admin = 'yes';
        } else {
            $by_admin = 'no';
        }

        if (isset($data['admincomment']) && $data['admincomment'] != "") {
            $admincomment = $data['admincomment'];
        } else {
            $admincomment = "";
        }
        if ($time || $distance) {

            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));

        }

        return response()->json([
            'status' => 'success',
            'message' => 'Record updated!'
        ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error in distanceFeed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            // Return a generic error response
            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod vid uppdatering av posten. Försök igen senare.' 
            ], 500);
        }
    }

    public function reopen(Request $request)
    {
        try {
            $data = $request->all();
    
            // Call the repository method and get the response
            $response = $this->repository->reopen($data);
    
            // Ensure the repository returns a structured JSON response
            return response()->json([
                'status' => 'success',
                'message' => $response['message'] ?? 'Jobbet har öppnats igen.',
                'data' => $response['data'] ?? []
            ], 200);
    
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in reopen method', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
    
            // Return a structured error response
            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod när jobbet skulle öppnas igen. Försök igen senare.'
            ], 500);
        }
    }
    

    public function resendNotifications(Request $request)
    {
        try {
            // Validate that 'jobid' is present
            $request->validate([
                'jobid' => 'required|integer|exists:jobs,id',
            ], [
                'jobid.required' => 'Jobb-ID krävs.', // "Job ID is required."
                'jobid.integer' => 'Jobb-ID måste vara ett giltigt nummer.', // "Job ID must be a valid number."
                'jobid.exists' => 'Jobbet kunde inte hittas.', // "The job could not be found."
            ]);

            $data = $request->all();
            $job = $this->repository->find($data['jobid']);

            if (!$job) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Jobbet kunde inte hittas.' // "The job could not be found."
                ], 404);
            }

            $job_data = $this->repository->jobToData($job);
            $this->repository->sendNotificationTranslator($job, $job_data, '*');

            return response()->json([
                'status' => 'success',
                'message' => 'Push-meddelande skickat.', // "Push notification sent."
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'fail',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in resendNotifications method', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'Ett fel uppstod när push-meddelandet skulle skickas. Försök igen senare.'
            ], 500);
        }
    }


    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        
        // Validate the job ID
        if (!isset($data['jobid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Job ID is required.'
            ], 400);
        }

        $job = $this->repository->find($data['jobid']);

        // Check if the job exists
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.'
            ], 404);
        }

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending SMS notification', [
                'job_id' => $data['jobid'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


     /**
     * Check if the user is an admin or superadmin.
     *
     * @param User $user
     * @return bool
     */
    protected function isAdminOrSuperAdmin($user)
    {
        $adminRoleId = env('ADMIN_ROLE_ID');
        $superAdminRoleId = env('SUPERADMIN_ROLE_ID');

        return $user->user_type === $adminRoleId || $user->user_type === $superAdminRoleId;
    }

}
