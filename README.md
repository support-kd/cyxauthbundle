# CyxAuthBundle
### 1. Instalation Process for private repositery:

``` json
# composer
"repositories": [
	{"type": "composer", "url": "https://tron.kolossus.com.au/repo/private/"}
],
```
### 2. Install FOSbundle
```
	composer require friendsofsymfony/user-bundle "~2.0@dev"
```
### 3.  Install CyxAuthBundle
```
	composer require KolossusD/CyxAuthBundle
```
### 4. Add to kernel
``` php
        $bundles = [
            new FOS\UserBundle\FOSUserBundle(),
            new Captcha\Bundle\CaptchaBundle\CaptchaBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
            new KolossusD\CyxAuthBundle\CyxAuthBundle(),
		]
			
	if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
		$bundles[] = new Csa\Bundle\GuzzleBundle\CsaGuzzleBundle();
	}
```
### 5. Add to config.yml:
``` yml

# Nelmio CORS
nelmio_cors:
    defaults:
        allow_origin:  ["%cors_allow_origin%"]
        allow_methods: ["POST", "PUT", "GET", "DELETE", "OPTIONS"]
        allow_headers: ["content-type", "authorization"]
        max_age:       3600
    paths:
        '^/': ~

# Configer you FOS bundle
fos_user:
    db_driver: orm # other valid values are 'mongodb', 'couchdb' and 'propel'
    firewall_name: main
    user_class: KolossusD\CyxAuthBundle\Entity\User
    from_email:
        address:        "%sender_email%"
        sender_name:    Sanjay Bharti
    registration:
        form:
            type: KolossusD\CyxAuthBundle\Form\RegistrationType
        confirmation:
            enabled: true
            template: Auth/Emails/registration.html.twig
            # if you are using Symfony < 2.8 you should use the type name instead
            # type: app_user_registration
    resetting:
        email:
            template: Auth/Emails/password_resetting.html.twig

# Nelmio API Doc
nelmio_api_doc: ~


# FOS REST Bundle
fos_rest:
    body_listener: true
    param_fetcher_listener: force
    format_listener:
        enabled: true
        rules:
            - { path: ^/api/, priorities: [ json ], fallback_format: json, prefer_extension: true }
            - { path: ^/, priorities: [ html ], fallback_format: html, prefer_extension: true }
    view:
        view_response_listener: 'force'
        formats:
            json: true
            xml: false
            rss: false
        mime_types:
            json: ['application/json', 'application/x-json']
    routing_loader:
        default_format:  json
        include_format:  false
    exception:
        enabled: true


#JMS Serializer
jms_serializer: ~


# CSA Guzzle
#    csa_guzzle:
#        profiler: "%kernel.debug%"
```
### 6. if you want to use REST API follow the step 6 and 7
### 6.a. Lexik JWT Bundle
```yml
lexik_jwt_authentication:
    private_key_path: "%jwt_private_key_path%"
    public_key_path:  "%jwt_public_key_path%"
    pass_phrase:      "%jwt_key_pass_phrase%"
    token_ttl:        "%jwt_token_ttl%"
```
### 6.b. Add line in parameter.yml and in parameter.yml.dist:
``` yml
    exception_email: 'test@example.com'
    jwt_private_key_path: '%kernel.root_dir%/../var/jwt/private.pem'
    jwt_public_key_path: '%kernel.root_dir%/../var/jwt/public.pem'
    jwt_key_pass_phrase: sanjay
    jwt_token_ttl: 86400
    cors_allow_origin: 'http://127.0.0.1:8282'
    api_name: 'Your API name'
    api_description: 'The full description of your API'
    sender_email: 'test@example.com'
    mailing_api: <postmark/sendgrid>
    postmark_resetting_template: <postMark_resset_password_template_id>
```

### 7. Generate the SSH keys :
``` bash
	$ mkdir -p var/jwt # For Symfony3+, no need of the -p option
	$ openssl genrsa -out var/jwt/private.pem -aes256 4096
	$ openssl rsa -pubout -in var/jwt/private.pem -out var/jwt/public.pem
```
### 8. Enable: in config.yml 
``` yml
	translator: { fallbacks: ["%locale%"] }
```
### 9. Router setting:
``` yml
	captcha_routing:
		resource: "@CaptchaBundle/Resources/config/routing.yml"
	user_API_login:
		path:     /api/login
		defaults: { _controller: "CyxAuthBundle:RestLogin:post" }
		methods:  [POST]
	cyx_auth:
		resource: "@CyxAuthBundle/Resources/config/routing.yml"
		prefix:   /auth
```
### 10. Add security in security.yml:
``` yml
# To get started with security, check out the documentation:
# http://symfony.com/doc/current/book/security.html
security:
    encoders:
        FOS\UserBundle\Model\UserInterface: bcrypt

    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: ROLE_ADMIN

    # http://symfony.com/doc/current/book/security.html#where-do-users-come-from-user-providers
    providers:
        fos_userbundle:
            id: fos_user.user_provider.username_email
        in_memory:
            memory: ~

    firewalls:
        # disables authentication for assets and the profiler, adapt it according to your needs
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        api_login:
            pattern:  ^/api/login
            stateless: true
            anonymous: true
            form_login:
                check_path:               /api/login
                require_previous_session: false
                username_parameter:       username
                password_parameter:       password
                success_handler:          lexik_jwt_authentication.handler.authentication_success
                failure_handler:          lexik_jwt_authentication.handler.authentication_failure
        api:
            pattern:   ^/api/
            stateless: true
            lexik_jwt: ~

        main:
            pattern: ^/
            form_login:
                provider: fos_userbundle
                csrf_token_generator: security.csrf.token_manager
                check_path: fos_user_security_check
                # if you are using Symfony < 2.8, use the following config instead:
                # csrf_provider: form.csrf_provider

            logout:
                path: fos_user_security_logout
            anonymous:    true
            # activate different ways to authenticate

            # http_basic: ~
            # http://symfony.com/doc/current/book/security.html#a-configuring-how-your-users-will-authenticate

            # form_login: ~
            # http://symfony.com/doc/current/cookbook/security/form_login_setup.html

    access_control:
        - { path: ^/auth/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/api/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/register, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/resetting, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin/, role: ROLE_ADMIN }
```
### 11. run the command: 
```
	 php bin/console cyx:auth:generate login-page
	 php bin/console doctrine:schema:update --force
```
### 12. Usage :
	For browser:
		localhost:8000/auth/login
		localhost:8000/auth/register
	For Rest API:
		localhost:8000/api/login
``` json
	{
		"username": "username",
		"password":"password"
	}
```
