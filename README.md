## Laravel Azure AD Token based authentication and authorization.

Add Azure AD Token Validation and role assignment via the following php packages:

	https://github.com/ohtarr/Azure - AzureAD Authentication and Token Validation
	https://github.com/JosephSilber/bouncer - Authorization

## Installation:

#### Add library to your Laravel project:
	composer require ohtarr/LaravelAzure

#### You must have an application registration in Azure with role based permissions setup.
#### Add the necessary env vars for Azure Active Directory OAUTH:
	AZURE_AD_TENANT=MyAwesomeAzureADTenant
	AZURE_AD_CLIENT_ID=1234abcd-12ab-34cd-56ef-123456abcdef
	AZURE_AD_CLIENT_SECRET=123456789abcdef123456789abcdef\123456789abc=

#### Publish LaravelAzure files - migration to add azure_id to users table, addPermission command to easily add bouncer permissions.
	php artisan vendor:publish --provider="Ohtarr\LaravelAzure\ServiceProvider" --force

#### Publish Bouncer files - Standard bouncer migrations to add tables for authorization control
	php artisan vendor:publish --provider="Silber\Bouncer\BouncerServiceProvider" --force

#### Migrate
	php artisan migrate

#### Add Bouncer to User model at /app/Models/User.php
	namespace App\Models;
	
	use Silber\Bouncer\Database\HasRolesAndAbilities;

	class User extends Authenticatable
	{
    		use HasRolesAndAbilities;
	}

#### Add permissions via addPermission command, or add bouncer permissions your own way...
	# modify addPermission file
	nano app/Console/Commands/addPermission.php
	
		#modify the objects array to include all of the objects you want to assign permissions to:
		$objects = [
			\App\Models\Thing::class,
		];

	# execute addPermission
	php artisan LaravelAzure:addPermission write Admin

#### add auth:api middleware to controllers or routes.  Controller example below.
	#controller constructor:
	public function __construct()
	{
		$this->middleware('auth:api');
	}

#### add authorization check to controller methods as needed

	public function index(Request $request)
	{
		//IF user is authorized
		$user = auth()->user();

		if ($user->cant('read', Model::class)) {
			abort(401, 'You are not authorized');
		}
		return $coolapistuff;
	}


