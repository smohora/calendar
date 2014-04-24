<?php
/**
 * Copyright (c) 2014 Georg Ehrke <oc.list@georgehrke.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCA\Calendar\Controller;

use \OCP\AppFramework\Http;

use \OCA\Calendar\Db\DoesNotExistException;
use \OCA\Calendar\BusinessLayer\BusinessLayerException;

use \OCA\Calendar\Db\Calendar;
use \OCA\Calendar\Db\CalendarCollection;

use \OCA\Calendar\Db\ObjectType;
use \OCA\Calendar\Db\Permission;

use \OCA\Calendar\Http\Response;

use \OCA\Calendar\Http\Reader;
use \OCA\Calendar\Http\Serializer;
use \OCA\Calendar\Http\ReaderExpcetion;
use \OCA\Calendar\Http\SerializerException;

class CalendarController extends Controller {

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		try {
			$userId = $this->api->getUserId();

			$nolimit = $this->params('nolimit', false);
			if ($nolimit) {
				$limit = $offset = null;
			} else {
				$limit = $this->params('limit', 25);
				$offset = $this->params('offset', 0);
			}

			$calendarCollection = $this->calendarBusinessLayer->findAll($userId, $limit, $offset);

			$serializer = new Serializer(Serializer::CalendarCollection, $calendarCollection, $this->accept());
			return new Response($serializer);
		} catch (BusinessLayerException $ex) {
			$this->app->log($ex->getMessage(), 'debug');
			return new Response(array('message' => $ex->getMessage()), $ex->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	 public function show() {
		try {
			$userId = $this->api->getUserId();
			$calendarId = $this->request->getParam('calendarId');

			$calendar = $this->calendarBusinessLayer->find($calendarId, $userId);

			$serializer = new Serializer(Serializer::Calendar, $calendar, $this->accept());
			return new Response($serializer);
		} catch (BusinessLayerException $ex) {
			$this->app->log($ex->getMessage(), 'debug');
			return new Response(array('message' => $ex->getMessage()), $ex->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function create() {
		try {
			$userId = $this->api->getUserId();
			$data = $this->request->params;

			$reader = new Reader(Reader::Calendar, $data, $this->contentType());

			$calendar = $reader->sanitize()->getObject();

			if ($calendar instanceof Calendar) {
				$calendar = $this->calendarBusinessLayer->createFromRequest($calendar);
				$serializer = new Serializer(Serializer::Calendar, $calendar, $this->accept());
			} elseif ($calendar instanceof CalendarCollection) {
				$calendar = $this->calendarBusinessLayer->createCollectionFromRequest($calendar);
				$serializer = new serializer(Serializer::CalendarCollection, $calendar, $this->accept());
			} else {
				throw new ReaderException('Reader returned unrecognised format.');
			}

			$serializer = new Serializer(Serializer::Calendar, $calendar, $this->accept());
			return new Response($serializer, Http::STATUS_CREATED);
		} catch (BusinessLayerException $ex) {
			$this->app->log($ex->getMessage(), 'debug');
			return new Response(array('message' => $ex->getMessage()), $ex->getCode());
		} catch(ReaderException $ex) {
			return new Response(array('message' => $ex->getMessage()), Http::STATUS_UNPROCESSABLE_ENTITY);
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function update() {
		try {
			$userId = $this->api->getUserId();
			$calendarId = $this->params('calendarId');
			$ctag = $this->header('if-match');
			$data = $this->request->params;

			$reader = new Reader(Reader::Calendar, $data, $this->contentType());

			$calendar = $reader->sanitize()->getObject();
			if ($calendar instanceof Calendar) {
				$calendar = $this->calendarBusinessLayer->updateFromRequest($calendar, $calendarId, $userId, $ctag);
			} elseif ($calendar instanceof CalendarCollection) {
				throw new ReaderException('Updates can only be applied to a single resource.', Http::STATUS_BAD_REQUEST);
			} else {
				throw new ReaderException('Reader returned unrecognised format.');
			}

			$serializer = new Serializer(Serializer::Calendar, $calendar, $this->accept());
			return new Response($serializer);
		} catch(BusinessLayerException $ex) {
			$this->app->log($ex->getMessage(), 'debug');
			return new Response(array('message' => $ex->getMessage()), $ex->getCode());
		} catch(ReaderException $ex) {
			return new Response(array('message' => $ex->getMessage()), Http::STATUS_UNPROCESSABLE_ENTITY);
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function destroy() {
		try {
			$userId	= $this->api->getUserId();
			$calendarId	= $this->params('calendarId');

			$calendar = $this->calendarBusinessLayer->find($calendarId, $userId);
			$this->calendarBusinessLayer->delete($calendar);

			return new Response();
		} catch (BusinessLayerException $ex) {
			$this->app->log($ex->getMessage(), 'debug');
			return new Response(array('message' => $ex->getMessage()), $ex->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function forceUpdate() {
		try {
			$userId	= $this->api->getUserId();
			$this->calendarBusinessLayer->updateCacheForAllFromRemote($userId);
			return new Response();
		} catch (BusinessLayerException $ex) {
			$this->app->log($ex->getMessage(), 'debug');
			return new Response(array('message' => $ex->getMessage()), $ex->getCode());
		}
	}
}