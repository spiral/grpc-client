# Changelog

## [1.0.0](https://github.com/spiral/grpc-client/compare/1.0.0-rc2...1.0.0) (2026-11-24)


### Bug Fixes

* accept a 1 ms timeout in `SetTimeoutInterceptor` ([#24](https://github.com/spiral/grpc-client/issues/24)) ([f22c0ae](https://github.com/spiral/grpc-client/commit/f22c0ae67949fa455e15581c079f59351cb8408c))
* apply the TLS `serverName` override to the channel ([#29](https://github.com/spiral/grpc-client/issues/29)) ([96bda30](https://github.com/spiral/grpc-client/commit/96bda30204f92a9fad239acf98990638a7320cc3))
* **deps:** require grpc/grpc 1.84.1 ([#45](https://github.com/spiral/grpc-client/issues/45)) ([39c8794](https://github.com/spiral/grpc-client/commit/39c87946690dbf17bb63a51e1b5a4ce6afde0140))
* generate clients for service interfaces in the global namespace ([#53](https://github.com/spiral/grpc-client/issues/53)) ([f9f6571](https://github.com/spiral/grpc-client/commit/f9f65713460341aa2a2bcd11838ba5ebf1e83d55))
* indexes for getMessage and getMetadata ([2c5226e](https://github.com/spiral/grpc-client/commit/2c5226e69edeab68cc5d1e91989324042b45a05d))
* indexes for getMessage and getMetadata ([51139f1](https://github.com/spiral/grpc-client/commit/51139f1e033a76e84cae89ef695979b6aeae69ec))
* keep retries within the deadline set before RetryInterceptor ([#60](https://github.com/spiral/grpc-client/issues/60)) ([5da3703](https://github.com/spiral/grpc-client/commit/5da37039ed8565004141ace134d21d6af81033a9))
* pass the declared parameters from generated service client methods ([#54](https://github.com/spiral/grpc-client/issues/54)) ([609ad28](https://github.com/spiral/grpc-client/commit/609ad286920d6b32a93e1351da5b0effa0734e76))
* pass the narrowed connection list to the next rotation attempt ([#32](https://github.com/spiral/grpc-client/issues/32)) ([1fc605d](https://github.com/spiral/grpc-client/commit/1fc605d8df9ec739ab90b062f950f15e91541dbc))
* reject a service client whose services have no connections ([#57](https://github.com/spiral/grpc-client/issues/57)) ([5be2280](https://github.com/spiral/grpc-client/commit/5be2280aa3a826fe502c943ef4e2f003b707762d))
* reject invalid RetryOptions maximum interval, attempts and backoff coefficient ([#46](https://github.com/spiral/grpc-client/issues/46)) ([882a459](https://github.com/spiral/grpc-client/commit/882a459bf0b935c410f938ddbea38fed1d68845b)), closes [#44](https://github.com/spiral/grpc-client/issues/44)
* render the static return type without a leading backslash ([#55](https://github.com/spiral/grpc-client/issues/55)) ([bd10492](https://github.com/spiral/grpc-client/commit/bd104921e15bc373b4943b754428c52ee20a3543))
* report a disconnected connection as not connected instead of throwing ([#56](https://github.com/spiral/grpc-client/issues/56)) ([6a4ef62](https://github.com/spiral/grpc-client/commit/6a4ef623f989244c1f49adca58c76079e61777d6))
* retry without waiting when `RetryInterceptor` intervals are 0 ([#28](https://github.com/spiral/grpc-client/issues/28)) ([d993433](https://github.com/spiral/grpc-client/commit/d993433b058398486bc526395f60b403dafe6a18))
* run service interceptors via `ExecuteServiceInterceptors` ([83672fd](https://github.com/spiral/grpc-client/commit/83672fd6fea37cae136829ea697c1df661d25dbb))


### Documentation

* Add project header and quality badges to README ([#36](https://github.com/spiral/grpc-client/issues/36)) ([549428b](https://github.com/spiral/grpc-client/commit/549428b6bcd513bb88f3d8f5d4ffc6f1fa0ff958))
* Add sources section to skills.json for GitHub package integration ([32745a1](https://github.com/spiral/grpc-client/commit/32745a180d01adc0ce301f12a50038da61868af8))
* Show `ConnectionsRotationInterceptor` before `ExecuteServiceInterceptors` in the config example ([e745e8f](https://github.com/spiral/grpc-client/commit/e745e8f68a826d9bf03480a1c7aa63414c684eb9))
* state the actual default maximum retry interval (200x the initial interval) ([#58](https://github.com/spiral/grpc-client/issues/58)) ([11b60f7](https://github.com/spiral/grpc-client/commit/11b60f7c47f4d88fd9dc5ea633efed6ea6d5c602))


### Code Refactoring

* fix Psalm issues and drop the baseline ([#27](https://github.com/spiral/grpc-client/issues/27)) ([3d9243a](https://github.com/spiral/grpc-client/commit/3d9243a3dc83e438c5ccecf38a6415c36524018a))
* fix Psalm redundant-condition errors without suppressions ([#41](https://github.com/spiral/grpc-client/issues/41)) ([358ef7a](https://github.com/spiral/grpc-client/commit/358ef7a72c8083bddf08ffca52e7a175b58fc436))
* return the public `Google\Protobuf\RepeatedField` from `ServiceClientException::getDetails()` ([#47](https://github.com/spiral/grpc-client/issues/47)) ([78fff56](https://github.com/spiral/grpc-client/commit/78fff56399e64825a3ab44f9202bee4565535240))
