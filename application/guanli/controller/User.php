<?php

namespace app\guanli\controller;

use think\Db;
use think\Request;
use app\admin\controller\Base;

class User extends Base
{
    public function index()
    {
        if (request()->isAjax()) {
            $get = input('get.');
            $limit = $get['limit'] ?? 10;
            $key = $get['key'] ?? '';
            $role_id = $get['role'] ?? 0;

            $where = 'user_id!=1';
           // $where .= ' and (r.role_id!=2)';
            $where .= ' and (r.role_id!=12)';
            if ($key) {
                $where .= " and (username like '%" . $key . "%'";
                $where .= " or realname like '%" . $key . "%')";
            }
            if ($role_id > 0) {
                $where .= " and (r.role_id=" . $role_id . ")";
            }

            $list = db('sys_user')->alias('u')
                ->join('sys_role r', 'u.role_id = r.role_id', 'left')
                ->field('u.*,r.name')
                ->where($where)
                ->order('role_id,u.user_id')
                ->paginate($limit)
                ->toArray();
            return $this->showList($list);
        } else {
            $list = db('sys_role')->select();
            $this->assign('list', $list);
            return view();
        }

    }

    public function userForm()
    {
        if (request()->isPost()) {
            $data = input('post.');
            if ($data['user_id'] == null) {
                $user = db('sys_user')->where(['username' => $data['username']])->find();
                if ($user) {
                    $this->error('用户名已经存在！');
                }
                $data['password'] = md5($data['password']);
                db('sys_user')->insert($data);
                return $this->success('用户添加成功！');
            } else {
                $user = db('sys_user')
                    ->where('username', $data['username'])
                    ->where('user_id', '<>', $data['user_id'])
                    ->find();
                if ($user) {
                    $this->error('用户名已经存在！');
                }
                db('sys_user')->update($data);
                return $this->success('用户编辑成功！');
            }
        } else {
            //添加此代码的目的为防止用户编辑时显示用户密码
            $this->assign('new', input('param.user_id'));

            $list = db('sys_role')->select();
            $this->assign('list', $list);
            return view('user_form');
        }
    }


    public function userInfo()
    {
        if (request()->isPost()) {
            $data = input('post.');
            db('sys_user')->strict(false)->update($data);
            return $this->success('用户信息更新成功！');

        } else {
            $user_id=session('user_id');
            $data = db('sys_user')->where('user_id=' . $user_id)->find();
            $this->assign('src', $data['avatar']);
            $this->assign('data', json_encode($data, true));
            return view('user_info');
        }
    }

    public function userPwd()
    {
        if (request()->isPost()) {
            $data = input('post.');
            db('sys_user')->update($data);
            return $this->success('用户信息更新成功！');

        } else {
            return view('user_pwd');
        }
    }

    public function userDel()
    {
        //参数后加/a是因为前面批量删除时会传来数组，如[1,2]
        db('sys_user')->delete(input('post.user_id/a'));
        return $this->success('删除成功!');
    }

    public function userStatus()
    {
        $status = input('post.status');
        if (db('sys_user')->where('user_id', input('post.user_id'))->update(['status' => $status]) !== false) {
            return $this->success('设置成功!');
        } else {
            return $this->error('设置失败!');
        }
    }

    public function resetPwd()
    {
        db('sys_user')->where('user_id', input('post.user_id'))->update(['password' => md5("1")]);
        return $this->success('重置密码成功，新密码为1!');
    }
}