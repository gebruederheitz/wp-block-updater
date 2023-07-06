dev:
	@cd js \
		&& npm i \
		&& npm run dev

build:
	@cd js \
		&& npm i \
		&& npm run build
	@mkdir -p templates/assets
	@cp js/dist/block-updater.js templates/assets/

# Run basic linting with prettier & phpstan
lint:
	@cd util/ \
		&& npm i \
		&& npm run lint
	@composer lint
	@cd js \
		&& npm i \
		&& npm run check

# Make prettier process and fix all files in src/
prettier:
	@cd util/ \
		&& npm i \
		&& npx prettier -w --config ../.prettierrc ../src

# Create a tagged release to publish a new version of the package
release: lint build
	@cd util/ \
		&& npm i \
		&& npm run release

