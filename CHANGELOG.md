# Changelog

## [1.0.0](https://github.com/spiral/grpc-client/compare/1.0.0-rc2...1.0.0) (2026-10-22)


### Bug Fixes

* accept a 1 ms timeout in `SetTimeoutInterceptor` ([#24](https://github.com/spiral/grpc-client/issues/24)) ([f22c0ae](https://github.com/spiral/grpc-client/commit/f22c0ae67949fa455e15581c079f59351cb8408c))
* apply the TLS `serverName` override to the channel ([#29](https://github.com/spiral/grpc-client/issues/29)) ([96bda30](https://github.com/spiral/grpc-client/commit/96bda30204f92a9fad239acf98990638a7320cc3))
* indexes for getMessage and getMetadata ([2c5226e](https://github.com/spiral/grpc-client/commit/2c5226e69edeab68cc5d1e91989324042b45a05d))
* indexes for getMessage and getMetadata ([51139f1](https://github.com/spiral/grpc-client/commit/51139f1e033a76e84cae89ef695979b6aeae69ec))
* pass the narrowed connection list to the next rotation attempt ([#32](https://github.com/spiral/grpc-client/issues/32)) ([1fc605d](https://github.com/spiral/grpc-client/commit/1fc605d8df9ec739ab90b062f950f15e91541dbc))
* retry without waiting when `RetryInterceptor` intervals are 0 ([#28](https://github.com/spiral/grpc-client/issues/28)) ([d993433](https://github.com/spiral/grpc-client/commit/d993433b058398486bc526395f60b403dafe6a18))


### Documentation

* Add project header and quality badges to README ([#36](https://github.com/spiral/grpc-client/issues/36)) ([549428b](https://github.com/spiral/grpc-client/commit/549428b6bcd513bb88f3d8f5d4ffc6f1fa0ff958))
* Add sources section to skills.json for GitHub package integration ([32745a1](https://github.com/spiral/grpc-client/commit/32745a180d01adc0ce301f12a50038da61868af8))


### Code Refactoring

* fix Psalm issues and drop the baseline ([#27](https://github.com/spiral/grpc-client/issues/27)) ([3d9243a](https://github.com/spiral/grpc-client/commit/3d9243a3dc83e438c5ccecf38a6415c36524018a))
